import React, { useState, useEffect, useContext } from 'react';
import { makeStyles } from "@mui/styles";
import InputAdornment from "@mui/material/InputAdornment";
import Icon from "@mui/material/Icon";
import Header from "components/Header/Header.js";
import HeaderLinks from "components/Header/HeaderLinks.js";
import Footer from "components/Footer/Footer.js";
import GridContainer from "components/Grid/GridContainer.js";
import GridItem from "components/Grid/GridItem.js";
import Button from "components/CustomButtons/Button.js";
import Card from "components/Card/Card.js";
import CardBody from "components/Card/CardBody.js";
import CardHeader from "components/Card/CardHeader.js";
import CardFooter from "components/Card/CardFooter.js";
import CustomInput from "components/CustomInput/CustomInput.js";
import styles from '../styles/loginPage.js';
import image from "assets/img/background.jpg";
import { useSelector, useDispatch } from "react-redux";
import AccountBoxIcon from '@mui/icons-material/AccountBox';
import AlertDialog from '../components/AlertDialog';
import CountrySelect from '../components/CountrySelect';
import { FirebaseContext, api } from 'common';
import { useTranslation } from "react-i18next";
import CircularProgress from '@mui/material/CircularProgress';
import { useNavigate } from 'react-router-dom';

const useStyles = makeStyles(styles);

export default function LoginPage(props) {
  const { authRef, RecaptchaVerifier } = useContext(FirebaseContext);
  const { t } = useTranslation();
  const {
    facebookSignIn,
    clearLoginError,
    mobileSignIn,
    countries,
    requestEmailOtp,
    verifyEmailOtp
  } = api;
  const navigate = useNavigate();
  const auth = useSelector(state => state.auth);
  const settings = useSelector(state => state.settingsdata.settings);
  const dispatch = useDispatch();
  const [capatchaReady, setCapatchaReady] = React.useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [verifier, setVerifier] = useState(null);

  const [data, setData] = React.useState({
    email: '',
    pass: '',
    country: null,
    contact: '',
    password: '',
    otp: '',
    verificationId: null,
    firstName: '',
    lastName: '',
    selectedcountry:null,
    usertype:'customer',
    referralId:'',
    entryType: null
  });

  const [otpCalled, setOtpCalled] = useState();

  const [commonAlert, setCommonAlert] = useState({ open: false, msg: '' });

  const classes = useStyles();
  const { ...rest } = props;

  useEffect(() => {
    if(settings){
        for (let i = 0; i < countries.length; i++) {
            if(countries[i].label === settings.country){
                setData({
                  country: countries[i].phone,
                  selectedcountry:countries[i],
                });
            }
        }
    }
  }, [settings,countries]);

  useEffect(() => {
    if(!window.recaptchaVerifier){
      setCapatchaReady(true);
    }
    if (auth.profile) {
      if(auth.profile.uid){
        let role = auth.profile.usertype;
        if(role==='admin' || role==='fleetadmin'){
          navigate('/dashboard');
        }
        else if (role==='driver'){
          navigate('/bookings');
        }
        else {
          navigate('/');
        }
      }
    }
    if (auth.error && auth.error.flag && auth.error.msg.message !== t('not_logged_in')) {
      if (auth.error.msg.message === t('require_approval')){
        setCommonAlert({ open: true, msg: t('require_approval') })
      } else{
        if(data.contact === '' ||  !(!data.contact) ){  
          setCommonAlert({ open: true, msg: t('login_error') })
        }
      }
    }
    if(auth.verificationId && otpCalled){
      setOtpCalled(false);
      setData({ ...data, verificationId: auth.verificationId });
    }
  }, [auth.profile, auth.error, auth.verificationId, navigate, data, data.email, data.contact, capatchaReady,RecaptchaVerifier,t, setData, otpCalled, setOtpCalled]);


  const handleFacebook = (e) => {
    e.preventDefault();
    dispatch(facebookSignIn());
  }

  const handleCommonAlertClose = (e) => {
    e.preventDefault();
    setCommonAlert({ open: false, msg: '' });
    if (auth.error.flag) {
      setData({...data,email:'',pass:''});
      dispatch(clearLoginError());
    }
  };

  const onInputChange = (event) => {
    setData({ ...data, [event.target.id]: event.target.value })
  }

  const handleGetOTP = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    if(data.country){
      if(data.contact){
        if (isNaN(data.contact)) {
          setData({...data, entryType: 'email'});
          const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
          if(re.test(data.contact)){
              setOtpCalled(true);
              await dispatch(requestEmailOtp(data.contact));
              setIsLoading(false);
          }else{
              setCommonAlert({ open: true, msg: t('proper_email')})
              setIsLoading(false);
          }
        } else{
          setData({...data, entryType: 'mobile'});
          let formattedNum = data.contact.replace(/ /g, '');
          if (formattedNum.length > 6) {
            if(!window.recaptchaVerifier || verifier===null){
              window.recaptchaVerifier = new RecaptchaVerifier("sign-in-button",{
                'size': 'invisible',
                'callback': function(response) {
                  setCapatchaReady(true);
                }
              });
            }
            const appVerifier = window.recaptchaVerifier;
            setVerifier(appVerifier);
            const phoneNumber = "+" + data.country + formattedNum;
            authRef
              .signInWithPhoneNumber(phoneNumber, appVerifier)
              .then(res => {
                  setData({...data, verificationId: res.verificationId})
                  setIsLoading(false)
                  appVerifier.reset();
              })
              .catch(error => {
                  setCommonAlert({ open: true, msg: error.code + ": " + error.message});
                  setIsLoading(false);
            });
          } else {
              setCommonAlert({ open: true, msg: t('mobile_no_blank_error')})
              setIsLoading(false);
          }
        }
      }else{
        setCommonAlert({ open: true, msg: t('contact_input_error')})
        setIsLoading(false);
      }
    }else{
      setCommonAlert({ open: true, msg: t('country_blank_error')})
      setIsLoading(false);
    }
  }

  const handleVerifyOTP = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    if(data.otp && data.otp.length === 6){
      if(data.entryType === 'email'){
          await dispatch(verifyEmailOtp(
              data.contact,
              data.otp
          ));
          setIsLoading(false);
      }else{
            await dispatch(mobileSignIn(
              data.verificationId,
              data.otp
          ));
          setIsLoading(false);
      }
    }else{
      setCommonAlert({ open: true, msg: t('otp_validate_error')})
      setIsLoading(false);
    }
  }

  const handleCancel = (e) => {
    setData({
      ...data,
      contact: null,
      verificationId: null
    });
    setIsLoading(false);
  }

  const onCountryChange = (object, value) => {
    if (value && value.phone) {
      setData({ ...data, country: value.phone, selectedcountry:value });
    }
  };

  return (
    <div>
      <Header
        absolute
        color="transparent"
        rightLinks={<HeaderLinks />}
        {...rest}
      />
      <div
        className={classes.pageHeader}
        style={{
          backgroundImage: "url(" + image + ")",
          backgroundSize: "cover",
          backgroundPosition: "top center",
          display:'flex'
        }}
      >
        <div id="sign-in-button" />
        <div className={classes.container}>
          <GridContainer justifyContent="center">
            <GridItem xs={12} sm={12} md={4}>
              <Card>
                <form className={classes.form}>
                  {settings && settings.FacebookLoginEnabled?
                  <CardHeader color="info" className={classes.cardHeader}>
                    <h4>{t('signIn')}</h4>
                    <div className={classes.socialLine}>
                      {settings.FacebookLoginEnabled?
                      <Button
                        justIcon
                        href="#pablo"
                        target="_blank"
                        color="transparent"

                        onClick={handleFacebook}
                      >
                        <i className={"fab fa-facebook"} />
                      </Button>
                      :null}
                    </div>
                  </CardHeader>
                  :null}
                  <CardBody>
        
                    {settings && settings.AllowCountrySelection && data.selectedcountry?   // COUNTRY
                      <CountrySelect
                        countries={countries}
                        label={t('select_country')}
                        value={data.selectedcountry}
                        onChange={onCountryChange}
                        style={{paddingTop:20}}
                        disabled={data.verificationId ? true : false}
                      />
                      : null}
                 
                      <CustomInput
                        labelText={t('contact_placeholder')}
                        id="contact"
                        formControlProps={{
                          fullWidth: true
                        }}
                        inputProps={{
                          required: true,
                          disabled: data.verificationId ? true : false,
                          endAdornment: (
                            <InputAdornment position="start">
                              <AccountBoxIcon className={classes.inputIconsColor} />
                            </InputAdornment>
                          )
                        }}
                        onChange={onInputChange}
                        value={data.contact}
                      />
        
                    {data.verificationId ?    // OTP
                      <CustomInput
                        labelText={t('otp')}
                        id="otp"
                        formControlProps={{
                          fullWidth: true
                        }}
                        inputProps={{
                          type: "password",
                          required: true,
                          endAdornment: (
                            <InputAdornment position="start">
                              <Icon className={classes.inputIconsColor}>
                                lock_outline
                            </Icon>
                            </InputAdornment>
                          ),
                          autoComplete: "off"
                        }}
                        onChange={onInputChange}
                        value={data.otp}
                      />
                      : null}
                  </CardBody>
                  <CardFooter className={classes.cardFooter}>
                  {isLoading ? <CircularProgress/> :
                    !data.verificationId ?
                      <Button className={classes.normalButton} simple color="primary" size="lg" type="submit" onClick={handleGetOTP}>
                        {t('request_otp')}
                      </Button>
                  :
                    <div>
                      <Button className={classes.normalButton} simple color="primary" size="lg" type="submit" onClick={handleVerifyOTP}>
                        {t('verify_otp')}
                      </Button>
                      <Button className={classes.normalButton} simple color="primary" size="lg" onClick={handleCancel}>
                      {t('cancel')}
                      </Button>
                    </div>
                  }
                  </CardFooter>
                </form>
              </Card>
            </GridItem>
          </GridContainer>
        </div>
        <Footer whiteFont />
        <AlertDialog open={commonAlert.open} onClose={handleCommonAlertClose}>{commonAlert.msg}</AlertDialog>
      </div>
    </div>
  );
}