import React, { useState, useEffect, useRef, useContext } from 'react';
import {
    View,
    Text,
    Dimensions,
    ScrollView,
    TouchableOpacity,
    KeyboardAvoidingView,
    Platform,
    StyleSheet,
    Alert,
    Image,
} from 'react-native';
import { Icon, Button, Input } from 'react-native-elements'
import { colors } from '../common/theme';
import ActionSheet from "react-native-actions-sheet";
import i18n from 'i18n-js';
var { height } = Dimensions.get('window');
import { useSelector, useDispatch } from 'react-redux';
import { api, FirebaseContext } from 'common';
import * as ImagePicker from 'expo-image-picker';
import { MaterialCommunityIcons, Entypo } from '@expo/vector-icons';
import Footer from '../components/Footer';
import Dialog from "react-native-dialog";
import { FirebaseRecaptchaVerifierModal } from "expo-firebase-recaptcha";
import { FontAwesome5 } from '@expo/vector-icons';
import { MAIN_COLOR } from '../common/sharedFunctions';

export default function EditProfilePage(props) {
    const { authRef, config } = useContext(FirebaseContext);
    const {
        updateProfile,
        requestEmailOtp,
        updateAuthEmail,
        signOut,
    } = api;
    const dispatch = useDispatch();
    const auth = useSelector(state => state.auth);
    const settings = useSelector(state => state.settingsdata.settings);
    const [profileData, setProfileData] = useState(null);
    const { t } = i18n;
    const isRTL = i18n.locale.indexOf('he') === 0 || i18n.locale.indexOf('ar') === 0;
    const actionSheetRef = useRef(null);
    const [capturedImage, setCapturedImage] = useState(null);
    const [capturedImageBack, setCapturedImageback] = useState(null);
    const [check, setCheck] = useState(null);
    const recaptchaVerifier = useRef(null);
    const [loading, setLoading] = useState(false);
    const [otp, setOtp] = useState("");
    const [otpCalled, setOtpCalled] = useState(false);
    const [confirmCodeFunction, setConfirmCodeFunction] = useState();
    const [updateCalled,setUpdateCalled] = useState(false);
    const { fromPage } = props.route.params;
    
    const onPressBack = () => {
        if(fromPage == 'DriverTrips' || fromPage == 'Map' || fromPage == 'Wallet'){
            props.navigation.navigate('TabRoot', { screen: fromPage });
        } else if(fromPage == 'Profile'){
            props.navigation.navigate('TabRoot', { screen: 'Settings' });
        }else{
            props.navigation.goBack() 
        }
    }

    useEffect(() => {
        if (auth.profile && auth.profile.uid) {
            setProfileData({ ...auth.profile });
            if(updateCalled){
                setLoading(false);
                Alert.alert(
                    t('alert'),
                    t('profile_updated'),
                    [
                        { text: t('ok'), onPress: () => { 
                            onPressBack();
                        }}
                    ],
                    { cancelable: true }
                );
                setUpdateCalled(false);
            }
        }
    }, [auth.profile, updateCalled]);

    const showActionSheet = (text) => {
        if(auth.profile && (auth.profile.mobile != profileData.mobile || auth.profile.email != profileData.email ) && (text == '0' || text == '1')){
            Alert.alert(t('alert'), t('not_on_same_time'))
        }else{
            setCheck(text);
            actionSheetRef.current?.setModalVisible(true);
        }
    }

    const [state, setState] = useState({
        licenseImage: null,
        licenseImageBack: null
    });

    const validateEmail = (email) => {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        const emailValid = re.test(email)
        return emailValid;
    }

    const uploadImage = () => {
        return (
            <ActionSheet ref={actionSheetRef}>
                <TouchableOpacity
                    style={{ width: '90%', alignSelf: 'center', paddingLeft: 20, paddingRight: 20, borderColor: colors.CONVERTDRIVER_TEXT, borderBottomWidth: 1, height: 60, alignItems: 'center', justifyContent: 'center' }}
                    onPress={() => { _pickImage('CAMERA', ImagePicker.launchCameraAsync) }}
                >
                    <Text style={{ color: colors.CAMERA_TEXT, fontWeight: 'bold' }}>{t('camera')}</Text>
                </TouchableOpacity>
                <TouchableOpacity
                    style={{ width: '90%', alignSelf: 'center', paddingLeft: 20, paddingRight: 20, borderBottomWidth: 1, borderColor: colors.CONVERTDRIVER_TEXT, height: 60, alignItems: 'center', justifyContent: 'center' }}
                    onPress={() => { _pickImage('MEDIA', ImagePicker.launchImageLibraryAsync) }}
                >
                    <Text style={{ color: colors.CAMERA_TEXT, fontWeight: 'bold' }}>{t('medialibrary')}</Text>
                </TouchableOpacity>
                <TouchableOpacity
                    style={{ width: '90%', alignSelf: 'center', paddingLeft: 20, paddingRight: 20, height: 50, alignItems: 'center', justifyContent: 'center' }}
                    onPress={() => { actionSheetRef.current?.setModalVisible(false); }}>
                    <Text style={{ color: 'red', fontWeight: 'bold' }}>{t('cancel')}</Text>
                </TouchableOpacity>
            </ActionSheet>
        )
    }

    const _pickImage = async (permissionType, res) => {
        var pickFrom = res;
        let permisions;
        if (permissionType == 'CAMERA') {
            permisions = await ImagePicker.requestCameraPermissionsAsync();
        } else {
            permisions = await ImagePicker.requestMediaLibraryPermissionsAsync();
        }
        const { status } = permisions;

        if (status == 'granted') {

            let result = await pickFrom({
                allowsEditing: true,
                aspect: [4, 3],
                base64: true,
            });

            actionSheetRef.current?.setModalVisible(false);

            if (!result.canceled) {
                let data = 'data:image/jpeg;base64,' + result.base64;
                if (check == 0) {
                    setCapturedImage(result.assets[0].uri);
                }
                else if (check == 1) {
                    setCapturedImageback(result.assets[0].uri);
                }

                const blob = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.onload = function () {
                        resolve(xhr.response);
                    };
                    xhr.onerror = function () {
                        Alert.alert(t('alert'), t('image_upload_error'));
                    };
                    xhr.responseType = 'blob';
                    xhr.open('GET', Platform.OS == 'ios' ? data : result.assets[0].uri, true);
                    xhr.send(null);
                });
                if (blob) {
                    if (check == 0) {
                        setState({ ...state, licenseImage: blob });
                    }
                    else if (check == 1) {
                        setState({ ...state, licenseImageBack: blob });
                    }
                }
            }
        } else {
            Alert.alert(t('alert'), t('camera_permission_error'))
        }
    }

    const cancelPhoto = () => {
        setCapturedImage(null);
    }

    const cancelPhotoback = () => {
        setCapturedImageback(null);
    }

    const completeSubmit = () => {
        let userData = {
            firstName: profileData.firstName,
            lastName: profileData.lastName,
            mobile: profileData.mobile,
            email: profileData.email
        }
        setUpdateCalled(true);
        if ((auth.profile.usertype == 'driver' && settings.bank_fields) || (auth.profile.usertype == 'customer' && settings.bank_fields && settings.RiderWithDraw) && profileData.bankAccount && profileData.bankAccount.length &&
            profileData.bankCode && profileData.bankCode.length &&
            profileData.bankName && profileData.bankName.length) {
            userData.bankAccount = profileData.bankAccount,
                userData.bankCode = profileData.bankCode,
                userData.bankName = profileData.bankName
        }
        if (auth.profile.usertype == 'driver') {
            if (capturedImage) {
                userData.licenseImage = state ? state.licenseImage : profileData.licenseImage ? profileData.licenseImage : null;
            }
            if (capturedImageBack) {
                userData.licenseImageBack = state ? state.licenseImageBack : profileData.licenseImageBack ? profileData.licenseImageBack : null;
            }
        }

        dispatch(updateProfile(userData));

        if(auth.profile && (auth.profile.mobile != profileData.mobile || auth.profile.email != profileData.email ) ){
            setTimeout(() => {
                dispatch(signOut());
            }, 1000);
        }
    }

    const saveProfile = async () => {
        if (!profileData.email) {
            Alert.alert(t('alert'), t('no_details_error'));
        } else {
            if (profileData.email !== auth.profile.email && profileData.mobile !== auth.profile.mobile) {
                Alert.alert(t('alert'), t('update_any'));
            } else {
                if (
                    profileData.firstName &&
                    profileData.firstName.length > 0 &&
                    profileData.lastName &&
                    profileData.lastName.length > 0
                ) {
                    if (profileData.email !== auth.profile.email) {
                        if (validateEmail(profileData.email)) {
                            setOtpCalled(true);
                            dispatch(requestEmailOtp(profileData.email));
                        } else {
                            Alert.alert(t('alert'), t('proper_email'));
                        }
                    } else if (profileData.mobile !== auth.profile.mobile) {
                        if (profileData.mobile && profileData.mobile.length > 6) {
                            setOtpCalled(true);
                            if (auth.profile.mobile && auth.profile.mobile.length > 6) {
                                await authRef.currentUser.unlink("phone")
                            }
                            const linkObj = await authRef.currentUser.linkWithPhoneNumber(profileData.mobile, recaptchaVerifier.current);
                            setConfirmCodeFunction(linkObj);
                        } else {
                            Alert.alert(t('alert'), t('mobile_no_blank_error'))
                        }
                    } else {
                        setLoading(true);
                        completeSubmit();
                    }
                }
                else {
                    Alert.alert(t('alert'), t('no_details_error'));
                }
            }
        }
    }
    const handleVerify = async () => {
        if (otp && otp.length === 6 && !isNaN(otp)) {
            setLoading(true);
            if (confirmCodeFunction) {
                confirmCodeFunction.confirm(otp).then((user) => {
                    completeSubmit()
                }).catch((error) => {
                    setOtp('');
                    setLoading(false);
                    Alert.alert(t('alert'), t('otp_validate_error'));
                })
            } else {
                const res = await updateAuthEmail(profileData.email, otp);
                if (res.success) {
                    completeSubmit();
                } else {
                    setOtp('');
                    setLoading(false);
                    if (res.error === 'Error updating user') {
                        Alert.alert(t('alert'), t('user_exists'));
                    } else {
                        Alert.alert(t('alert'), t('otp_validate_error'));
                    }
                }
            }
        } else {
            setOtp('');
            Alert.alert(t('alert'), t('otp_validate_error'));
        }
        setOtpCalled(false);
    }

    const handleClose = () => {
        setOtpCalled(false);
    }

    const lCom = () => {
        return (
          <TouchableOpacity style={{ marginLeft: 10}} onPress={onPressBack}>
            <FontAwesome5 name="arrow-left" size={24} color={colors.WHITE} />
          </TouchableOpacity>
        );
      }
    
    React.useEffect(() => {
        props.navigation.setOptions({
            headerLeft: lCom,
        });
    }, [props.navigation]);


    return (
        <View style={[styles.mainView, { backgroundColor: colors.WHITE }]}>
            <FirebaseRecaptchaVerifierModal
                ref={recaptchaVerifier}
                firebaseConfig={config}
                androidHardwareAccelerationDisabled
                attemptInvisibleVerification={true}
            />
            <View style={{ backgroundColor: MAIN_COLOR, height: '83%' }}>
                <View style={styles.vew1}>
                <KeyboardAvoidingView style={styles.form} behavior={Platform.OS == "ios" ? "padding" : (__DEV__ ? null : "padding" )}>
                        <ScrollView style={styles.scrollViewStyle} showsVerticalScrollIndicator={false}>
                            <View style={styles.containerStyle}>

                                <View style={[styles.textInputContainerStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                    <View style={styles.iconContainer}>
                                        <MaterialCommunityIcons name="account-outline" size={24} color={colors.HEADER} />
                                    </View>
                                    <View style={{ width: '75%', }}>
                                        <Input
                                            editable={true}
                                            underlineColorAndroid={colors.TRANSPARENT}
                                            placeholder={t('first_name_placeholder')}
                                            placeholderTextColor={colors.PROFILE_PLACEHOLDER_TEXT}
                                            value={profileData && profileData.firstName ? profileData.firstName : ''}
                                            keyboardType={'email-address'}
                                            inputStyle={[styles.inputTextStyle, isRTL ? { textAlign: 'right', fontSize: 13, } : { textAlign: 'left', fontSize: 13, }]}
                                            onChangeText={(text) => { setProfileData({ ...profileData, firstName: text }) }}
                                            secureTextEntry={false}
                                            errorStyle={styles.errorMessageStyle}
                                            inputContainerStyle={[styles.inputContainerStyle]}
                                            containerStyle={[styles.textInputStyle, isRTL ? { marginLeft: 5 } : { marginRight: 5 }]}
                                        />
                                    </View>
                                </View>

                                <View style={[styles.textInputContainerStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                    <View style={styles.iconContainer}>
                                        <MaterialCommunityIcons name="account-outline" size={24} color={colors.HEADER} />
                                    </View>
                                    <View style={{ width: '75%' }}>
                                        <Input
                                            editable={true}
                                            underlineColorAndroid={colors.TRANSPARENT}
                                            placeholder={t('last_name_placeholder')}
                                            placeholderTextColor={colors.PROFILE_PLACEHOLDER_TEXT}
                                            value={profileData && profileData.lastName ? profileData.lastName : ''}
                                            keyboardType={'email-address'}
                                            inputStyle={[styles.inputTextStyle, isRTL ? { textAlign: 'right', fontSize: 13, } : { textAlign: 'left', fontSize: 13, }]}
                                            onChangeText={(text) => { setProfileData({ ...profileData, lastName: text }) }}
                                            secureTextEntry={false}
                                            errorStyle={styles.errorMessageStyle}
                                            inputContainerStyle={styles.inputContainerStyle}
                                            containerStyle={[styles.textInputStyle, isRTL ? { marginLeft: 5 } : { marginRight: 5 }]}
                                        />
                                    </View>
                                </View>
                                <View style={[styles.textInputContainerStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                    <View style={styles.iconContainer}>
                                        <Entypo name="email" size={24} color={colors.HEADER} />
                                    </View>
                                    <View style={{ width: '75%' }}>
                                        <Input
                                            underlineColorAndroid={colors.TRANSPARENT}
                                            placeholder={t('email_placeholder')}
                                            placeholderTextColor={colors.PROFILE_PLACEHOLDER_TEXT}
                                            value={profileData && profileData.email ? profileData.email : ''}
                                            keyboardType={'email-address'}
                                            inputStyle={[styles.inputTextStyle, isRTL ? { textAlign: 'right', fontSize: 13, } : { textAlign: 'left', fontSize: 13, }]}
                                            onChangeText={(text) => { setProfileData({ ...profileData, email: text }) }}
                                            secureTextEntry={false}
                                            blurOnSubmit={true}
                                            errorStyle={styles.errorMessageStyle}
                                            inputContainerStyle={styles.inputContainerStyle}
                                            containerStyle={[styles.textInputStyle, { marginLeft: 5 }]}
                                        />
                                    </View>
                                </View>
                                <View style={[styles.textInputContainerStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                    <View style={styles.iconContainer}>
                                        <MaterialCommunityIcons name="cellphone-information" size={24} color={colors.HEADER} />
                                    </View>
                                    <View style={{ width: '75%' }}>
                                        <Input
                                            underlineColorAndroid={colors.TRANSPARENT}
                                            placeholder={t('mobile')}
                                            placeholderTextColor={colors.PROFILE_PLACEHOLDER_TEXT}
                                            value={profileData && profileData.mobile ? profileData.mobile : ''}
                                            keyboardType={'phone-pad'}
                                            inputStyle={[styles.inputTextStyle, isRTL ? { textAlign: 'right', fontSize: 13, } : { textAlign: 'left', fontSize: 13, }]}
                                            onChangeText={(text) => {
                                                setProfileData({ ...profileData, mobile: text })
                                            }}
                                            secureTextEntry={false}
                                            errorStyle={styles.errorMessageStyle}
                                            inputContainerStyle={styles.inputContainerStyle}
                                            containerStyle={styles.textInputStyle}
                                        />
                                    </View>
                                </View>
                                {(auth.profile.usertype == 'driver' && settings.bank_fields) || (auth.profile.usertype == 'customer' && settings.bank_fields && settings.RiderWithDraw) ?
                                    <View style={[styles.textInputContainerStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                        <View style={styles.iconContainer}>
                                            <MaterialCommunityIcons name="bank-outline" size={24} color={colors.HEADER} />
                                        </View>
                                        <View style={{ width: '75%' }}>
                                            <Input
                                                editable={true}
                                                underlineColorAndroid={colors.TRANSPARENT}
                                                placeholder={t('bankName')}
                                                placeholderTextColor={colors.PROFILE_PLACEHOLDER_TEXT}
                                                value={profileData && profileData.bankName ? profileData.bankName : ''}
                                                keyboardType={'email-address'}
                                                inputStyle={[styles.inputTextStyle, isRTL ? { textAlign: 'right', fontSize: 13, } : { textAlign: 'left', fontSize: 13, }]}
                                                onChangeText={(text) => { setProfileData({ ...profileData, bankName: text }) }}
                                                secureTextEntry={false}
                                                errorStyle={styles.errorMessageStyle}
                                                inputContainerStyle={styles.inputContainerStyle}
                                                containerStyle={[styles.textInputStyle, { marginLeft: 0 }]}
                                            />
                                        </View>
                                    </View>
                                    : null}
                                {(auth.profile.usertype == 'driver' && settings.bank_fields) || (auth.profile.usertype == 'customer' && settings.bank_fields && settings.RiderWithDraw) ?
                                    <View style={[styles.textInputContainerStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                        <Icon
                                            name='numeric'
                                            type='material-community'
                                            color={colors.PROFILE_PLACEHOLDER_CONTENT}
                                            size={30}
                                            containerStyle={styles.iconContainer}
                                        />
                                        <View style={{ width: '75%' }}>
                                            <Input
                                                editable={true}
                                                underlineColorAndroid={colors.TRANSPARENT}
                                                placeholder={t('bankCode')}
                                                placeholderTextColor={colors.PROFILE_PLACEHOLDER_TEXT}
                                                value={profileData && profileData.bankCode ? profileData.bankCode : ''}
                                                keyboardType={'email-address'}
                                                inputStyle={[styles.inputTextStyle, isRTL ? { textAlign: 'right', fontSize: 13, } : { textAlign: 'left', fontSize: 13, }]}
                                                onChangeText={(text) => { setProfileData({ ...profileData, bankCode: text }) }}
                                                secureTextEntry={false}
                                                errorStyle={styles.errorMessageStyle}
                                                inputContainerStyle={styles.inputContainerStyle}
                                                containerStyle={[styles.textInputStyle, { marginLeft: 0 }]}
                                            />
                                        </View>
                                    </View>
                                    : null}
                                {(auth.profile.usertype == 'driver' && settings.bank_fields) || (auth.profile.usertype == 'customer' && settings.bank_fields && settings.RiderWithDraw) ?
                                    <View style={[styles.textInputContainerStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                        <Icon
                                            name='numeric'
                                            type='material-community'
                                            color={colors.PROFILE_PLACEHOLDER_CONTENT}
                                            size={30}
                                            containerStyle={styles.iconContainer}
                                        />
                                        <View style={{ width: '75%' }}>
                                            <Input
                                                editable={true}
                                                underlineColorAndroid={colors.TRANSPARENT}
                                                placeholder={t('bankAccount')}
                                                placeholderTextColor={colors.PROFILE_PLACEHOLDER_TEXT}
                                                value={profileData && profileData.bankAccount ? profileData.bankAccount : ''}
                                                keyboardType={'email-address'}
                                                inputStyle={[styles.inputTextStyle, isRTL ? { textAlign: 'right', fontSize: 13, } : { textAlign: 'left', fontSize: 13, }]}
                                                onChangeText={(text) => { setProfileData({ ...profileData, bankAccount: text }) }}
                                                secureTextEntry={false}
                                                errorStyle={styles.errorMessageStyle}
                                                inputContainerStyle={styles.inputContainerStyle}
                                                containerStyle={[styles.textInputStyle, { marginLeft: 0 }]}
                                            />
                                        </View>
                                    </View>
                                    : null}

                                {auth.profile.usertype == 'driver' ?
                                    !auth.profile.licenseImage ?
                                        capturedImage ?
                                            <View style={styles.imagePosition}>
                                                <TouchableOpacity style={styles.photoClick} onPress={cancelPhoto}>
                                                    <Image source={require('../../assets/images/cross.png')} resizeMode={'contain'} style={styles.imageStyle} />
                                                </TouchableOpacity>
                                                <Image source={{ uri: capturedImage }} style={styles.photoResult} resizeMode={'cover'} />
                                            </View>
                                            :
                                            <View style={styles.capturePhoto}>
                                                <View>
                                                    {
                                                        state.imageValid ?
                                                            <Text style={styles.capturePhotoTitle}>{t('upload_driving_license_front')}</Text>
                                                            :
                                                            <Text style={styles.errorPhotoTitle}>{t('upload_driving_license_front')}</Text>
                                                    }

                                                </View>
                                                <View style={[styles.capturePicClick, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                                    <TouchableOpacity style={styles.flexView1} onPress={() => showActionSheet('0')}>
                                                        <View>
                                                            <View style={styles.imageFixStyle}>
                                                                <Image source={require('../../assets/images/camera.png')} resizeMode={'contain'} style={styles.imageStyle2} />
                                                            </View>
                                                        </View>
                                                    </TouchableOpacity>
                                                    <View style={styles.myView}>
                                                        <View style={styles.myView1} />
                                                    </View>
                                                    <View style={styles.myView2}>
                                                        <View style={styles.myView3}>
                                                            <Text style={styles.textStyle}>{t('image_size_warning')}</Text>
                                                        </View>
                                                    </View>
                                                </View>
                                            </View>
                                        :
                                        <View style={styles.imagePosition}>

                                            {capturedImage ?
                                                <TouchableOpacity style={styles.photoClick} onPress={cancelPhoto}>
                                                    <Image source={require('../../assets/images/cross.png')} resizeMode={'contain'} style={styles.imageStyle} />
                                                </TouchableOpacity>
                                                : null}

                                            {capturedImage ?
                                                <TouchableOpacity onPress={() => showActionSheet('0')}>
                                                    <Image source={{ uri: capturedImage }} style={styles.photoResult} resizeMode={'cover'} />
                                                </TouchableOpacity>
                                                :
                                                <TouchableOpacity onPress={() => showActionSheet('0')}>
                                                    <Image source={{ uri: auth.profile.licenseImage }} style={styles.photoResult} resizeMode={'cover'} />
                                                </TouchableOpacity>
                                            }
                                        </View>
                                    : null
                                }

                                {auth.profile.usertype == 'driver' ?
                                    !auth.profile.licenseImageBack ?
                                        capturedImageBack ?
                                            <View style={styles.imagePosition}>
                                                <TouchableOpacity style={styles.photoClick} onPress={cancelPhotoback}>
                                                    <Image source={require('../../assets/images/cross.png')} resizeMode={'contain'} style={styles.imageStyle} />
                                                </TouchableOpacity>
                                                <Image source={{ uri: capturedImageBack }} style={styles.photoResult} resizeMode={'cover'} />
                                            </View>
                                            :
                                            <View style={styles.capturePhoto}>
                                                <View>
                                                    {
                                                        state.imageValid ?
                                                            <Text style={styles.capturePhotoTitle}>{t('upload_driving_license_back')}</Text>
                                                            :
                                                            <Text style={styles.errorPhotoTitle}>{t('upload_driving_license_back')}</Text>
                                                    }

                                                </View>
                                                <View style={[styles.capturePicClick, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                                                    <TouchableOpacity style={styles.flexView1} onPress={() => showActionSheet('1')}>
                                                        <View>
                                                            <View style={styles.imageFixStyle}>
                                                                <Image source={require('../../assets/images/camera.png')} resizeMode={'contain'} style={styles.imageStyle2} />
                                                            </View>
                                                        </View>
                                                    </TouchableOpacity>
                                                    <View style={styles.myView}>
                                                        <View style={styles.myView1} />
                                                    </View>
                                                    <View style={styles.myView2}>
                                                        <View style={styles.myView3}>
                                                            <Text style={styles.textStyle}>{t('image_size_warning')}</Text>
                                                        </View>
                                                    </View>
                                                </View>
                                            </View>
                                        :
                                        <View style={styles.imagePosition}>

                                            {capturedImageBack ?
                                                <TouchableOpacity style={styles.photoClick} onPress={cancelPhotoback}>
                                                    <Image source={require('../../assets/images/cross.png')} resizeMode={'contain'} style={styles.imageStyle} />
                                                </TouchableOpacity>
                                                : null}

                                            {capturedImageBack ?
                                                <TouchableOpacity onPress={() => showActionSheet('1')}>
                                                    <Image source={{ uri: capturedImageBack }} style={styles.photoResult} resizeMode={'cover'} />
                                                </TouchableOpacity>
                                                :
                                                <TouchableOpacity onPress={() => showActionSheet('1')}>
                                                    <Image source={{ uri: auth.profile.licenseImageBack }} style={styles.photoResult} resizeMode={'cover'} />
                                                </TouchableOpacity>
                                            }
                                        </View>
                                    : null
                                }

                                <View style={styles.buttonContainer}>
                                    <Button
                                        loading={loading}
                                        onPress={saveProfile}
                                        title={t('update_button')}
                                        titleStyle={styles.buttonTitle}
                                        buttonStyle={[styles.registerButton, { backgroundColor: MAIN_COLOR }]}
                                    />
                                </View>
                                <View style={styles.gapView} />
                            </View>
                        </ScrollView>
                    </KeyboardAvoidingView>
                    {
                        uploadImage()
                    }

                </View>
            </View>
            <Dialog.Container visible={otpCalled}>
                <Dialog.Description style={{color:colors.HEADER,fontWeight:'bold'}}>{auth.profile && profileData && (auth.profile.mobile != profileData.mobile) ? t('check_mobile') : t('check_email')}</Dialog.Description>
                <Dialog.Input  placeholder= {t('otp_here')} placeholderTextColor ={colors.HEADER} keyboardType = 'numeric' onChangeText={(otp) => setOtp(otp)} style={{color:colors.HEADER}}></Dialog.Input>
                <Dialog.Button label={t('cancel')} onPress={handleClose} style={{marginRight:15,color:colors.HEADER}}/>
                <Dialog.Button label={t('ok')} onPress={handleVerify} style={{marginRight:10,color:colors.SKY}}/>
            </Dialog.Container>
            <Footer />
        </View>
    );

}


const styles = StyleSheet.create({
    pickerStyle: {
        color: colors.HEADER,
        width: 200,
        fontSize: 15,
        height: 40,
        marginBottom: 27,
        margin: 10,
        borderBottomWidth: 0.5,
        borderBottomColor: colors.HEADER,
        
    },
    container: {
        height: '100%',
        width: '100%',
    },
    vew: {
        borderTopLeftRadius: 130,
        height: '100%',
        alignItems: 'flex-end'
    },
    textInputContainerStyle: {
        width: '90%',
        height: 65,
        borderRadius: 10,
        marginVertical: 10,
        shadowColor: "#000",
        shadowOffset: {
            width: 0,
            height: 3,
        },
        shadowOpacity: 0.3,
        shadowRadius: 3,
        elevation: 3,
        backgroundColor: colors.WHITE,
        alignItems: 'center'
    },
    vew1: {
        height: '100%',
        borderBottomRightRadius: 120,
        overflow: 'hidden',
        backgroundColor: colors.WHITE,
        width: '100%'
    },
    textInputStyle: {
    },
    inputContainerStyle: {
        width: "100%",
    },
    iconContainer: {
        width: '15%',
        alignItems: 'center'
    },
    gapView: {
        height: 40,
        width: '100%'
    },
    buttonContainer: {
        flexDirection: 'row',
        justifyContent: 'center',
        borderRadius: 40
    },
    registerButton: {
        width: 180,
        height: 50,
        borderColor: colors.TRANSPARENT,
        borderWidth: 0,
        marginTop: 30,
        borderRadius: 15,
    },
    buttonTitle: {
        fontSize: 16
    },
    inputTextStyle: {
        color: colors.HEADER,
        fontSize: 13,
        height: 32,
    },
    errorMessageStyle: {
        fontSize: 12,
        fontWeight: 'bold',
        marginLeft: 0
    },
    containerStyle: {
        flexDirection: 'column',
        width: '100%',
        marginTop: 10
    },
    logo: {
        width: '65%',
        justifyContent: "center",
        height: '15%',
        borderBottomRightRadius: 150,
        shadowColor: "black",
        shadowOffset: {
            width: 0,
            height: 8,
        },
        shadowOpacity: 0.34,
        shadowRadius: 6.27,
        elevation: 5,
        marginBottom: 5,
    },
    headerStyle: {
        fontSize: 25,
        color: colors.WHITE,
        flexDirection: 'row',
        width: '80%'
    },
    imagePosition: {
        position: 'relative'
    },
    imageStyle: {
        width: 30,
        height: height / 15
    },
    photoResult: {
        alignSelf: 'center',
        flexDirection: 'column',
        justifyContent: 'center',
        borderRadius: 10,
        marginLeft: 20,
        marginRight: 20,
        paddingTop: 15,
        paddingBottom: 10,
        marginTop: 15,
        width: '80%',
        height: height / 4
    },
    capturePhoto: {
        width: '60%',
        height: 110,
        alignSelf: 'center',
        flexDirection: 'column',
        justifyContent: 'center',
        borderRadius: 10,
        marginTop: 15,
        shadowColor: "#000",
        shadowOffset: {
            width: 0,
            height: 5,
        },
        shadowOpacity: 0.2,
        shadowRadius: 3,
        elevation: 3,
        backgroundColor: colors.WHITE
    },
    capturePhotoTitle: {
        color: colors.BLACK,
        fontSize: 14,
        textAlign: 'center',
        paddingBottom: 15,

    },
    errorPhotoTitle: {
        color: colors.RED,
        fontSize: 13,
        textAlign: 'center',
        paddingBottom: 15,
    },
    photoClick: {
        paddingRight: 48,
        position: 'absolute',
        zIndex: 1,
        marginTop: 18,
        alignSelf: 'flex-end'
    },
    capturePicClick: {
        backgroundColor: colors.WHITE,
        flexDirection: 'row',
        position: 'relative',
        zIndex: 1
    },
    imageStyle: {
        width: 30,
        height: height / 15
    },
    flexView1: {
        flex: 12
    },
    imageFixStyle: {
        alignItems: 'center',
        justifyContent: 'center'
    },
    imageStyle2: {
        width: 150,
        height: height / 15
    },
    myView: {
        flex: 2,
        height: 50,
        width: 1,
        alignItems: 'center'
    },
    myView1: {
        height: height / 18,
        width: 1.5,
        backgroundColor: colors.CONVERTDRIVER_TEXT,
        alignItems: 'center',
        marginTop: 10
    },
    myView2: {
        flex: 20,
        alignItems: 'center',
        justifyContent: 'center'
    },
    myView3: {
        flex: 2.2,
        alignItems: 'center',
        justifyContent: 'center'
    },
});
