import {
  FETCH_ALL_USERS,
  FETCH_ALL_USERS_SUCCESS,
  FETCH_ALL_USERS_FAILED,
  EDIT_USER,
  EDIT_USER_SUCCESS,
  EDIT_USER_FAILED,
  DELETE_USER,
  DELETE_USER_SUCCESS,
  DELETE_USER_FAILED,
  FETCH_ALL_USERS_STATIC,
  FETCH_ALL_USERS_STATIC_SUCCESS,
  FETCH_ALL_USERS_STATIC_FAILED,
  USER_DELETED
} from "../store/types";
import { firebase } from '../config/configureFirebase';

export const fetchUsers = () => (dispatch) => {

  const {
    usersRef,
    allLocationsRef
  } = firebase;

  dispatch({
    type: FETCH_ALL_USERS,
    payload: null
  });
  usersRef.on("value", async snapshot => {
    if (snapshot.val()) {
      const locationdata = await allLocationsRef.once("value");
      const locations = locationdata.val();
      const data = snapshot.val();
      const arr = Object.keys(data)
      .filter(i => data[i].usertype!='admin')
      .map(i => {
        data[i].id = i;
        data[i].location = locations && locations[i] ? locations[i] : null;
        return data[i];
      });
      dispatch({
        type: FETCH_ALL_USERS_SUCCESS,
        payload: arr
      });
    } else {
      dispatch({
        type: FETCH_ALL_USERS_FAILED,
        payload: "No users available."
      });
    }
  });
};


export const fetchUsersOnce = () => (dispatch) => {

  const {
    usersRef,
    allLocationsRef
  } = firebase;

  dispatch({
    type: FETCH_ALL_USERS_STATIC,
    payload: null
  });
  usersRef.once("value", async snapshot => {
    if (snapshot.val()) {
      const locationdata = await allLocationsRef.once("value");
      const locations = locationdata.val();
      const data = snapshot.val();
      const arr = Object.keys(data)
      .map(i => {
        data[i].id = i;
        data[i].location = locations && locations[i] ? locations[i] : null;
        return data[i];
      });
      dispatch({
        type: FETCH_ALL_USERS_STATIC_SUCCESS,
        payload: arr
      })
    } else {
      dispatch({
        type: FETCH_ALL_USERS_STATIC_FAILED,
        payload: "No users available."
      });
    }
  });
};


export const fetchDrivers = () => (dispatch) => {

  const {
    usersRef,
    allLocationsRef
  } = firebase;

  dispatch({
    type: FETCH_ALL_USERS,
    payload: null
  });

  usersRef.orderByChild("queue").equalTo(false).once("value", snapshot => {
    if (snapshot.val()) {
      allLocationsRef.once("value", locres=>{
        const locations = locres.val();
          const data = snapshot.val();
          const arr = Object.keys(data)
          .filter(i => data && data[i].usertype=='driver' && data[i].approved == true && data[i].driverActiveStatus == true && locations && locations[i] && data[i].licenseImage && data[i].carApproved)
          .map(i => {
            return {
              id: i,
              location: locations && locations[i] ? locations[i]:null,
              carType: data[i].carType
            };
          });
          dispatch({
            type: FETCH_ALL_USERS_SUCCESS,
            payload: arr
          });
      })
    } else {
      dispatch({
        type: FETCH_ALL_USERS_FAILED,
        payload: "No users available."
      });
    }
  });
};

export const addUser = (userdata) => (dispatch) => {
  const {
    usersRef
  } = firebase;

  dispatch({
    type: EDIT_USER,
    payload: userdata
  });

  delete userdata.tableData;

  usersRef.push(userdata).then(() => {
    dispatch({
      type: EDIT_USER_SUCCESS,
      payload: null
    });
  }).catch((error) => {
    dispatch({
      type: EDIT_USER_FAILED,
      payload: error
    });
  });
}

export const editUser = (id, user) => (dispatch) => {

  const {
    singleUserRef
  } = firebase;

  dispatch({
    type: EDIT_USER,
    payload: user
  });
  let editedUser = user;
  delete editedUser.id;
  delete editedUser.tableData;
  singleUserRef(id).set(editedUser);
}

export const updateUserCar = (id, data) => (dispatch) => {
  const {
    singleUserRef
  } = firebase;

  dispatch({
    type: EDIT_USER,
    payload: data  
  });
  singleUserRef(id).update(data);
}

export const updateLicenseImage = (uid, imageBlob, imageType) => async (dispatch) => {

  const {
    singleUserRef,
    driverDocsRef,
    driverDocsRefBack
  } = firebase;

  let profile = {};
  if(imageType === 'licenseImage'){
    await driverDocsRef(uid).put(imageBlob);
    let image = await driverDocsRef(uid).getDownloadURL();
    profile.licenseImage = image;
  }
  if(imageType === 'licenseImageBack'){
    await driverDocsRefBack(uid).put(imageBlob);
    let image1 = await driverDocsRefBack(uid).getDownloadURL();
    profile.licenseImageBack = image1;
  }
  singleUserRef(uid).update(profile);
  dispatch({
    type: EDIT_USER,
    payload: uid
  });
};

export const deleteUser = (uid) => (dispatch) => {

  const {
    auth,
    walletHistoryRef,
    singleUserRef,
    userNotificationsRef,
    carsRef,
    carEditRef
  } = firebase;

  dispatch({
    type: DELETE_USER,
    payload: uid
  });

  if (auth.currentUser.uid === uid) {
    singleUserRef(uid).off();
    walletHistoryRef(uid).off();
    userNotificationsRef(uid).off();
  }

  singleUserRef(uid).once("value", userdata => {
    const profile = userdata.val();
    if(profile.usertype === 'driver'){
      carsRef(uid, profile.usertype).on("value", carssnapshot => {
        let cars = carssnapshot.val();
        if (cars) {
          const arr = Object.keys(cars);
          for(let i = 0; i < arr.length; i++){
            carEditRef(arr[i]).remove();
          }
        }
      });
    } 
    
    singleUserRef(uid).remove().then(() => {
      if (auth.currentUser.uid === uid) {
        auth.signOut();
        dispatch({
          type: USER_DELETED,
          payload: null
        });
      } else {
        singleUserRef(uid).remove().then(() => {
          dispatch({
            type: DELETE_USER_SUCCESS,
            payload: null
          });
        }).catch((error) => {
          dispatch({
            type: DELETE_USER_FAILED,
            payload: error
          });
        });
      }
    });
  });
}
