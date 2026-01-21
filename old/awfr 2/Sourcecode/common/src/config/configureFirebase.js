
import React, { createContext } from 'react';
import app from 'firebase/app';
import 'firebase/database';
import 'firebase/auth';
import 'firebase/storage';

const FirebaseContext = createContext(null);

let firebase = {
    app: null,
    database: null,
    auth: null,
    storage: null,
}

const createFullStructure = (app,config) => {
    return {
        app: app,
        config: config,
        database: app.database(),
        auth: app.auth(),
        storage: app.storage(),
        authRef: app.auth(),
        facebookProvider:new app.auth.FacebookAuthProvider(),
        googleProvider:new app.auth.GoogleAuthProvider(),
        appleProvider:new app.auth.OAuthProvider('apple.com'),
        phoneProvider:new app.auth.PhoneAuthProvider(),          
        RecaptchaVerifier: app.auth.RecaptchaVerifier,
        captchaGenerator: (element) => new app.auth.RecaptchaVerifier(element),           
        facebookCredential: (token) => app.auth.FacebookAuthProvider.credential(token),
        googleCredential: (idToken) => app.auth.GoogleAuthProvider.credential(idToken),
        mobileAuthCredential: (verificationId,code) => app.auth.PhoneAuthProvider.credential(verificationId, code),           
        usersRef: app.database().ref("users"),
        bookingRef:app.database().ref("bookings"),
        cancelreasonRef:app.database().ref("cancel_reason"),
        settingsRef:app.database().ref("settings"),
        carTypesRef:app.database().ref("cartypes"),   
        carTypesEditRef:(id) => app.database().ref("cartypes/"+ id),
        carDocImage:(id) => app.storage().ref(`cartypes/${id}`),            
        promoRef:app.database().ref("promos"),
        promoEditRef:(id) => app.database().ref("promos/"+ id),
        notifyRef:app.database().ref("notifications/"),
        notifyEditRef:(id) => app.database().ref("notifications/"+ id),
        singleUserRef:(uid) => app.database().ref("users/" + uid),
        profileImageRef:(uid) => app.storage().ref(`users/${uid}/profileImage`),
        bookingImageRef:(bookingId,imageType) => app.storage().ref(`bookings/${bookingId}/${imageType}`),
        driverDocsRef:(uid) => app.storage().ref(`users/${uid}/license`),       
        driverDocsRefBack:(uid) => app.storage().ref(`users/${uid}/licenseBack`),         
        singleBookingRef:(bookingKey) => app.database().ref("bookings/" + bookingKey),
        requestedDriversRef:(bookingKey ) => app.database().ref("bookings/" + bookingKey  + "/requestedDrivers"),
        referralIdRef:(referralId) => app.database().ref("users").orderByChild("referralId").equalTo(referralId),
        trackingRef: (bookingId) => app.database().ref('tracking/' + bookingId),
        tasksRef:() => app.database().ref('bookings').orderByChild('status').equalTo('NEW'),
        singleTaskRef:(uid,bookingId) => app.database().ref("bookings/" + bookingId  + "/requestedDrivers/" + uid),
        bookingListRef:(uid,role) => 
            role == 'customer'? app.database().ref('bookings').orderByChild('customer').equalTo(uid):
                (role == 'driver'? 
                    app.database().ref('bookings').orderByChild('driver').equalTo(uid)
                    :
                    (role == 'fleetadmin'? 
                        app.database().ref('bookings').orderByChild('fleetadmin').equalTo(uid)
                        : app.database().ref('bookings')
                    )
                ),
        chatRef:(bookingId) => app.database().ref('chats/' + bookingId + '/messages'),
        withdrawRef:app.database().ref('withdraws/'),
        languagesRef:app.database().ref("languages"),
        languagesEditRef:(id) => app.database().ref("languages/"+ id),
        walletHistoryRef:(uid) => app.database().ref("walletHistory/" + uid),  
        userNotificationsRef:(uid) =>  app.database().ref("userNotifications/"+ uid),
        userRatingsRef:(uid) =>  app.database().ref("userRatings/"+ uid),
        carsRef:(uid,role) => role == 'driver'? 
            app.database().ref('cars').orderByChild('driver').equalTo(uid)
            :(role == 'fleetadmin'? 
                app.database().ref('cars').orderByChild('fleetadmin').equalTo(uid)
                : app.database().ref('cars')
            ),
        carAddRef: app.database().ref("cars"),
        carEditRef:(id) => app.database().ref("cars/"+ id),
        carImage:(id) => app.storage().ref(`cars/${id}`),   
        allLocationsRef: app.database().ref("locations"),
        userLocationRef:(uid) => app.database().ref("locations/"+ uid),
    }
}

const FirebaseProvider  = ({ config, children }) => {
    if (app.apps && app.apps.length == 0) {
        app.initializeApp(config);
        firebase = createFullStructure(app, config);
    } else {
        firebase = createFullStructure(app, config);
    }
    return (
        <FirebaseContext.Provider value={firebase}>
            {children}
        </FirebaseContext.Provider>
    )
}

export {
    firebase,
    FirebaseProvider,
    FirebaseContext
}