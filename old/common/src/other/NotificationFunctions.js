import { firebase } from '../config/configureFirebase';

export const RequestPushMsg = (token, data) => {
    const {
        config
    } = firebase;
    
    fetch(`https://${config.projectId}.web.app/send_notification`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            "token": token,
            ...data
        })
    })
    .then((response) => {

    })
    .catch((error) => {
        console.log(error)
    });
}