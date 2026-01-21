import {
  FETCH_USER,
  FETCH_USER_SUCCESS,
  FETCH_USER_FAILED,
  USER_SIGN_IN,
  USER_SIGN_IN_FAILED,
  USER_SIGN_OUT,
  CLEAR_LOGIN_ERROR,
  REQUEST_OTP,
  REQUEST_OTP_SUCCESS,
  REQUEST_OTP_FAILED,
  UPDATE_USER_WALLET_HISTORY
} from "../store/types";

import store from '../store/store';
import { firebase } from '../config/configureFirebase';

export const fetchUser = () => (dispatch) => {
  const {
    auth,
    singleUserRef
  } = firebase;

  dispatch({
    type: FETCH_USER,
    payload: null
  });
  auth.onAuthStateChanged(user => {
    if (user) {
      singleUserRef(user.uid).on("value", async snapshot => {
        if (snapshot.val()) {
          let profile = snapshot.val();
          profile.uid = user.uid;
          dispatch({
            type: FETCH_USER_SUCCESS,
            payload: profile
          });
        }else{
          let mobile = '';
          let email =  '';
          let firstName = '';
          let lastName = '';
          let profile_image = null;
          if(user.providerData.length == 0 && user.email){
            email = user.email;
          }
          if(user.providerData.length > 0 && user.phoneNumber){
            mobile = user.phoneNumber;
          }
          if (user.providerData.length > 0) {
            const provideData = user.providerData[0];
            if (provideData == 'phone') {
              mobile = provideData.phoneNumber;
            }
            if (provideData.providerId == 'facebook.com' || provideData.providerId == 'apple.com') {
              if (provideData.email) {
                email = provideData.email;
              }
              if (provideData.phoneNumber) {
                mobile = provideData.phoneNumber;
              }
              if (provideData.displayName) {
                if (provideData.displayName.split(" ").length > 0) {
                  firstName = provideData.displayName.split(" ")[0];
                  lastName = provideData.displayName.split(" ")[1];
                } else {
                  firstName = provideData.displayName;
                }
              }
              if (provideData.photoURL) {
                profile_image = provideData.photoURL;
              }
            }
          }
          const c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
          const reference = [...Array(5)].map(_ => c[~~(Math.random()*c.length)]).join('');
          let userData = {
            createdAt: new Date().getTime(),
            firstName: firstName,
            lastName: lastName,
            mobile: mobile,
            email: email,
            usertype: 'customer',
            referralId: reference,
            approved: true,
            walletBalance: 0
          }
          if(profile_image){
            userData['profile_image'] = profile_image;
          }
          singleUserRef(user.uid).set(userData);
          userData.uid = user.uid;
          dispatch({
            type: FETCH_USER_SUCCESS,
            payload: userData
          });
        }
      });
    } else {
      dispatch({
        type: FETCH_USER_FAILED,
        payload: { code: store.getState().languagedata.defaultLanguage.auth_error, message: store.getState().languagedata.defaultLanguage.not_logged_in }
      });
    }
  });
};

export const validateReferer = async (referralId) => {
  const {
    config
  } = firebase;
  const response = await fetch(`https://${config.projectId}.web.app/validate_referrer`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      referralId: referralId
    })
  })
  const json = await response.json();
  return json;
};

export const checkUserExists = async (regData) => {
  const {
    config
  } = firebase;
  const response = await fetch(`https://${config.projectId}.web.app/check_user_exists`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      email: regData.email,
      mobile: regData.mobile
    })
  })
  const json = await response.json();
  return json;
};

export const mainSignUp = async (regData) => {
  const {
    config,
    driverDocsRef
  } = firebase;
  let url = `https://${config.projectId}.web.app/user_signup`;
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ regData: regData })
  })
  const res = await response.json();
  return res;
};

export const requestEmailOtp = (email) => async (dispatch) => {
  const {
    config
  } = firebase;
  dispatch({
    type: REQUEST_OTP,
    payload: true
  });
  let url = `https://${config.projectId}.web.app/request_email_otp`;
  try{
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email: email })
    });
    const result = await response.json();
    if(result.success){
      dispatch({
        type: REQUEST_OTP_SUCCESS,
        payload: true
      });
    }else{
      dispatch({
        type: REQUEST_OTP_FAILED,
        payload: result.error
      });
    }
  }catch(error){
    console.log(error);
    dispatch({
      type: REQUEST_OTP_FAILED,
      payload: error
    });
  }
}

export const verifyEmailOtp = (email, otp) => async (dispatch) => {
  const {
    auth,
    config
  } = firebase;
  const body = {
    email: email,
    otp: otp
  };
  try{
    let url = `https://${config.projectId}.web.app/verify_email_otp`;
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body)
    })
    const result = await response.json();
    if(result.token){
      auth.signInWithCustomToken(result.token)
        .then((user) => {
          //OnAuthStateChange takes care of Navigation
        })
        .catch((error) => {
          dispatch({
            type: USER_SIGN_IN_FAILED,
            payload: error
          });
        });
    }else{
      dispatch({
        type: USER_SIGN_IN_FAILED,
        payload: result.error
      });
    }
  }catch(error){
    console.log(error);
    dispatch({
      type: USER_SIGN_IN_FAILED,
      payload: error
    });
  }
}

export const updateAuthEmail = async ( email, otp) => {
  const {
    auth,
    config
  } = firebase;

  const uid = auth.currentUser.uid;
  const body = {
    uid: uid,
    email: email,
    otp: otp
  };
  try{
    let url = `https://${config.projectId}.web.app/update_auth_email`;
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(body)
    })
    const result = await response.json();
    if(result.success){
      return {success: true}
    }else{
      return {success: false, error: result.error}
    }
  }catch(error){
    return {success: false, error: error}
  }
}


export const requestPhoneOtpDevice = (phoneNumber, appVerifier) => async (dispatch) => {
  const {
    phoneProvider
  } = firebase;
  dispatch({
    type: REQUEST_OTP,
    payload: null
  });
  try {
    const verificationId = await phoneProvider.verifyPhoneNumber(
      phoneNumber,
      appVerifier
    );
    dispatch({
      type: REQUEST_OTP_SUCCESS,
      payload: verificationId
    });
  }
  catch (error) {
    dispatch({
      type: REQUEST_OTP_FAILED,
      payload: error
    });
  };
}

export const mobileSignIn = (verficationId, code) => (dispatch) => {
  const {
    auth,
    mobileAuthCredential,
  } = firebase;

  dispatch({
    type: USER_SIGN_IN,
    payload: null
  });
  auth.signInWithCredential(mobileAuthCredential(verficationId, code))
    .then((user) => {
      //OnAuthStateChange takes care of Navigation
    }).catch(error => {
      dispatch({
        type: USER_SIGN_IN_FAILED,
        payload: error
      });
    });
};

export const facebookSignIn = (token) => (dispatch) => {

  const {
    auth,
    facebookProvider,
    facebookCredential
  } = firebase;

  dispatch({
    type: USER_SIGN_IN,
    payload: null
  });
  if (token) {
    const credential = facebookCredential(token);
    auth.signInWithCredential(credential)
      .then((user) => {
        //OnAuthStateChange takes care of Navigation
      })
      .catch(error => {
        dispatch({
          type: USER_SIGN_IN_FAILED,
          payload: error
        });
      }
      );
  } else {
    auth.signInWithPopup(facebookProvider).then(function (result) {
      var token = result.credential.accessToken;
      const credential = facebookCredential(token);
      auth.signInWithCredential(credential)
        .then((user) => {
          //OnAuthStateChange takes care of Navigation
        })
        .catch(error => {
          dispatch({
            type: USER_SIGN_IN_FAILED,
            payload: error
          });
        }
        );
    }).catch(function (error) {
      dispatch({
        type: USER_SIGN_IN_FAILED,
        payload: error
      });
    });
  }
};

export const appleSignIn = (credentialData) => (dispatch) => {

  const {
    auth,
    appleProvider
  } = firebase;

  dispatch({
    type: USER_SIGN_IN,
    payload: null
  });
  if (credentialData) {
    const credential = appleProvider.credential(credentialData);
    auth.signInWithCredential(credential)
      .then((user) => {
        //OnAuthStateChange takes care of Navigation
      })
      .catch((error) => {
        dispatch({
          type: USER_SIGN_IN_FAILED,
          payload: error
        });
      });
  } else {
    auth.signInWithPopup(appleProvider).then(function (result) {
      auth.signInWithCredential(result.credential)
        .then((user) => {
        //OnAuthStateChange takes care of Navigation
        })
        .catch(error => {
          dispatch({
            type: USER_SIGN_IN_FAILED,
            payload: error
          });
        }
        );
    }).catch(function (error) {
      dispatch({
        type: USER_SIGN_IN_FAILED,
        payload: error
      });
    });
  }
};

export const signOut = () => (dispatch) => {

  const {
    auth,
    singleUserRef,
    walletHistoryRef,
    userNotificationsRef
  } = firebase;

  const uid = auth.currentUser.uid;

  singleUserRef(uid).off();
  walletHistoryRef(uid).off();
  userNotificationsRef(uid).off();

  singleUserRef(uid).once('value', snapshot => {
      if(snapshot.val()){
        const profile = snapshot.val();
        if (profile && profile.usertype === 'driver') {
          singleUserRef(uid).update({driverActiveStatus:false});
        }
        setTimeout(()=>{
          auth
          .signOut()
          .then(() => {
            dispatch({
              type: USER_SIGN_OUT,
              payload: null
            });
          })
          .catch(error => {
      
          });
        },2000)
      }
  });
};

export const updateProfile = (updateData) => async (dispatch) => {

  const {
    auth,
    singleUserRef,
    driverDocsRef,
    driverDocsRefBack
  } = firebase;

  const uid = auth.currentUser.uid;

  if (updateData.licenseImage) {
    await driverDocsRef(uid).put(updateData.licenseImage);
    updateData.licenseImage = await driverDocsRef(uid).getDownloadURL();
  }
  if (updateData.licenseImageBack) {
    await driverDocsRefBack(uid).put(updateData.licenseImageBack);
    updateData.licenseImageBack = await driverDocsRefBack(uid).getDownloadURL();
  }

  singleUserRef(uid).update(updateData);
};


export const updateProfileImage = (imageBlob) => {

  const {
    auth,
    singleUserRef,
    profileImageRef,
  } = firebase;

  const uid = auth.currentUser.uid;

  profileImageRef(uid).put(imageBlob).then(() => {
    imageBlob.close()
    return profileImageRef(uid).getDownloadURL()
  }).then((url) => {
    singleUserRef(uid).update({
      profile_image: url
    });
  })
};

export const updateWebProfileImage = async (imageBlob) => {

  const {
    auth,
    singleUserRef,
    profileImageRef
  } = firebase;

  const uid = auth.currentUser.uid;

  await profileImageRef(uid).put(imageBlob);
  let image = await profileImageRef(uid).getDownloadURL();
  singleUserRef(uid).update({profile_image: image});

};

export const updatePushToken = (token, platform)  => {

  const {
    auth,
    singleUserRef,
  } = firebase;

  const uid = auth.currentUser.uid;

  singleUserRef(uid).update({
    pushToken: token,
    userPlatform: platform
  });
};

export const clearLoginError = () => (dispatch) => {
  dispatch({
    type: CLEAR_LOGIN_ERROR,
    payload: null
  });
};

export const fetchWalletHistory = () => (dispatch) => {
  const {
    auth,
    walletHistoryRef
  } = firebase;

  const uid = auth.currentUser.uid;

  walletHistoryRef(uid).on('value', snapshot => {
    const data = snapshot.val(); 
    if(data){
      const arr = Object.keys(data).map(i => {
        data[i].id = i
        return data[i]
      });
      dispatch({
        type: UPDATE_USER_WALLET_HISTORY,
        payload: arr
      });
    }
  });
};

