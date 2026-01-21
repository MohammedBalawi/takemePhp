import React, { useState, useEffect, useRef } from 'react';
import {
    StyleSheet,
    View,
    Image,
    Dimensions,
    Text,
    TouchableOpacity,
    ScrollView,
    ActivityIndicator,
    Alert,
    Switch,
    Platform,
    Share
} from 'react-native';
import { Icon, Header } from 'react-native-elements';
import ActionSheet from "react-native-actions-sheet";
import { colors } from '../common/theme';
import * as ImagePicker from 'expo-image-picker';
import i18n from 'i18n-js';
var { width, height } = Dimensions.get('window');
import { useSelector, useDispatch } from 'react-redux';
import { api } from 'common';
import StarRating from 'react-native-star-rating';
import AsyncStorage from '@react-native-async-storage/async-storage';
import RNPickerSelect from 'react-native-picker-select';
import moment from 'moment/min/moment-with-locales';
import { CommonActions } from '@react-navigation/native';
import { Ionicons, Entypo, MaterialCommunityIcons, AntDesign } from '@expo/vector-icons';
import * as TaskManager from 'expo-task-manager';
import * as Location from 'expo-location';
import { MAIN_COLOR } from '../common/sharedFunctions';

export default function ProfileScreen(props) {
    const { t } = i18n;
    const [isRTL, setIsRTL] = useState();
    const {
        updateProfileImage,
        deleteUser,
        updateProfile,
        signOut
    } = api;
    const dispatch = useDispatch();
    const auth = useSelector(state => state.auth);
    const settings = useSelector(state => state.settingsdata.settings);
    const [profileData, setProfileData] = useState(null);
    const [loader, setLoader] = useState(false);
    const actionSheetRef = useRef(null);
    const [langSelection, setLangSelection] = useState();
    const languagedata = useSelector(state => state.languagedata);

    useEffect(() => {
        setLangSelection(i18n.locale);
        setIsRTL(i18n.locale.indexOf('he') === 0 || i18n.locale.indexOf('ar') === 0);
    }, []);

    useEffect(() => {
        if (auth.profile && auth.profile.uid) {
            setProfileData(auth.profile);
        }
    }, [auth.profile]);

    const onChangeFunction = () => {
        let res = !profileData.driverActiveStatus;
        dispatch(updateProfile({ driverActiveStatus: res }));
    }

    const showActionSheet = () => {
        actionSheetRef.current?.setModalVisible(true);
    }

    const StopBackgroundLocation = async () => {
        TaskManager.getRegisteredTasksAsync().then((res) => {
            if (res.length > 0) {
                for (let i = 0; i < res.length; i++) {
                    if (res[i].taskName == 'background-location-task') {
                        Location.stopLocationUpdatesAsync('background-location-task');
                        break;
                    }
                }
            }
        });
    }

    const logOff = () => {
        auth.info && auth.info.profile && auth.info.profile.usertype == 'driver' ? StopBackgroundLocation() : null;
        setTimeout(() => {
            dispatch(signOut());
        }, 1000);
    }
    const uploadImage = () => {

        return (
            <ActionSheet ref={actionSheetRef}>
                <TouchableOpacity
                    style={{ width: '90%', alignSelf: 'center', paddingLeft: 20, paddingRight: 20, borderColor: colors.BORDER_TEXT, borderBottomWidth: 1, height: 60, alignItems: 'center', justifyContent: 'center' }}
                    onPress={() => { _pickImage('CAMERA', ImagePicker.launchCameraAsync) }}
                >
                    <Text style={{ color: colors.CAMERA_TEXT, fontWeight: 'bold' }}>{t('camera')}</Text>
                </TouchableOpacity>
                <TouchableOpacity
                    style={{ width: '90%', alignSelf: 'center', paddingLeft: 20, paddingRight: 20, borderBottomWidth: 1, borderColor: colors.BORDER_TEXT, height: 60, alignItems: 'center', justifyContent: 'center' }}
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
            setLoader(true);
            let result = await pickFrom({
                allowsEditing: true,
                aspect: [3, 3],
                base64: true
            });
            actionSheetRef.current?.setModalVisible(false);
            if (!result.canceled) {
                let data = 'data:image/jpeg;base64,' + result.base64;
                setProfileData({
                    ...profileData,
                    profile_image: result.assets[0].uri
                })
                const blob = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.onload = function () {
                        resolve(xhr.response);
                    };
                    xhr.onerror = function () {
                        Alert.alert(t('alert'), t('image_upload_error'));
                        setLoader(false);
                    };
                    xhr.responseType = 'blob';
                    xhr.open('GET', Platform.OS == 'ios' ? data : result.assets[0].uri, true);
                    xhr.send(null);
                });
                if (blob) {
                    updateProfileImage(blob);
                }
                setLoader(false);
            }
            else {
                setLoader(false);
            }
        } else {
            Alert.alert(t('alert'), t('camera_permission_error'))
        }
    };


    const editProfile = () => {
        props.navigation.dispatch(CommonActions.reset({ index: 0, routes: [{ name: 'editUser', params: { fromPage: 'Profile' } }] }));
    }

    //Delete current user
    const deleteAccount = () => {
        Alert.alert(
            t('delete_account_modal_title'),
            t('delete_account_modal_subtitle'),
            [
                {
                    text: t('cancel'),
                    onPress: () => { },
                    style: 'cancel',
                },
                {
                    text: t('yes'), onPress: () => {
                        dispatch(deleteUser(auth.profile.uid));
                    }
                },
            ],
            { cancelable: false },
        );
    }
    return (
        <View style={styles.mainView}>
            <View style={[styles.viewStyle, { backgroundColor: MAIN_COLOR }]} >
                <Text style={styles.textPropStyle} >{profileData && profileData.firstName.toUpperCase() + " " + profileData.lastName.toUpperCase()}</Text>
                <View style={styles.vew1}>
                    <View style={styles.imageViewStyle} >
                        {loader ?
                            <View style={[styles.loadingcontainer, styles.horizontal]}>
                                <ActivityIndicator size="large" color={colors.INDICATOR_BLUE} />
                            </View>
                            : <TouchableOpacity onPress={showActionSheet}>
                                <Image source={profileData && profileData.profile_image ? { uri: profileData.profile_image } : require('../../assets/images/profilePic.png')} style={{ width: 95, height: 95, alignSelf: 'center', borderRadius: 95 / 2 }} />
                            </TouchableOpacity>
                        }

                    </View>
                    <View style={{ flexDirection: 'row', height: 60, width: '50%', justifyContent: 'space-around', marginTop: 10, alignSelf: 'center' }}>
                        <View style={styles.vew}>
                            <TouchableOpacity onPress={editProfile}>
                                <AntDesign name="edit" size={25} color={colors.BUTTON_YELLOW} style={{ alignSelf: 'center', marginTop: 3 }} />
                                <Text style={[styles.emailStyle, { color: colors.BUTTON_YELLOW }]}>{t('edit')}</Text>
                            </TouchableOpacity>
                        </View>
                        {/* <View style={styles.vew}>
                        <TouchableOpacity onPress={deleteAccount}>
                            <MaterialCommunityIcons name="delete-empty-outline" size={25} color={colors.DULL_RED} style={{ alignSelf: 'center', marginTop: 3 }} />
                            <Text style={[styles.emailStyle, { color: colors.DULL_RED }]}>{t('delete_account_lebel')}</Text>
                        </TouchableOpacity>
                    </View> */}
                        <View style={styles.vew}>
                            <TouchableOpacity onPress={logOff}>
                                <AntDesign name="logout" size={20} color={colors.BUTTON_BACKGROUND} style={{ alignSelf: 'center', marginTop: 7 }} />
                                <Text style={[styles.emailStyle, { color: colors.BUTTON_BACKGROUND }]}>{t('logout')}</Text>
                            </TouchableOpacity>
                        </View>
                    </View>

                </View>

            </View>
            <ScrollView showsVerticalScrollIndicator={false} style={styles.scrollStyle}>
                {
                    uploadImage()
                }
                <View style={styles.newViewStyle}>
                    {languagedata && languagedata.langlist ?
                        <View style={[styles.myViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                            <View style={styles.iconViewStyle}>

                                <Ionicons name="language-sharp" size={25} color={colors.PROFILE_PLACEHOLDER_CONTENT} />
                            </View>
                            <View style={[styles.flexView1, { alignSelf: isRTL ? 'flex-end' : 'flex-start', }]}>
                                <Text style={[styles.text1, [isRTL ? { marginRight: 15 } : null]]}>{t('lang')}</Text>
                                <TouchableOpacity style={{ flexDirection: isRTL ? 'row-reverse' : 'row' }}>
                                    {langSelection ?
                                        <RNPickerSelect
                                            placeholder={{}}
                                            value={langSelection}
                                            useNativeAndroidPickerStyle={false}
                                            style={{
                                                inputIOS: [styles.pickerStyle, [isRTL ? { marginRight: 0 } : { marginLeft: 15 }]],
                                                inputAndroid: [styles.pickerStyle1, [isRTL ? { marginRight: -20 } : { marginLeft: 12 }]],
                                                placeholder: {
                                                    color: colors.PROFILE_PLACEHOLDER_CONTENT
                                                },

                                            }}
                                            onValueChange={
                                                (text) => {
                                                    let defl = null;
                                                    for (const value of Object.values(languagedata.langlist)) {
                                                        if (value.langLocale == text) {
                                                            defl = value;
                                                        }
                                                    }
                                                    setLangSelection(text);
                                                    i18n.locale = text;
                                                    moment.locale(defl.dateLocale);
                                                    setIsRTL(text == 'he' || text == 'ar')
                                                    AsyncStorage.setItem('lang', JSON.stringify({ langLocale: text, dateLocale: defl.dateLocale }));
                                                    dispatch(updateProfile({ lang: { langLocale: text, dateLocale: defl.dateLocale } }));
                                                }
                                            }
                                            label={"Language"}
                                            items={Object.values(languagedata.langlist).map(function (value) { return { label: value.langName, value: value.langLocale }; })}

                                        />
                                        : null}
                                    <Ionicons name="arrow-down" size={25} color="black" />
                                </TouchableOpacity>
                            </View>
                        </View>
                        : null}
                    <View style={[styles.myViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                        <View style={styles.iconViewStyle}>

                            <Entypo name="email" size={25} color={colors.PROFILE_PLACEHOLDER_CONTENT} />
                        </View>
                        <View style={[styles.flexView1, [isRTL ? { marginRight: 15 } : null]]}>
                            <Text style={styles.text1}>{t('email_placeholder')}</Text>
                            <Text style={styles.text2}>{profileData ? profileData.email : ''}</Text>
                        </View>
                    </View>


                    <View style={[styles.myViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                        <View style={[styles.iconViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                            <Icon
                                name='phone-call'
                                type='feather'
                                size={25}
                                color={colors.PROFILE_PLACEHOLDER_CONTENT}
                            />

                        </View>
                        <View style={[styles.flexView1, [isRTL ? { marginRight: 15 } : null]]}>
                            <Text style={styles.text1}>{t('mobile')}</Text>
                            <Text style={styles.text2}>{profileData ? profileData.mobile : ''}</Text>
                        </View>
                    </View>
                    {profileData && profileData.referralId ?
                        <View style={[styles.myViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                            <View style={[styles.iconViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row', }]}>
                                <Icon
                                    name='award'
                                    type='feather'
                                    color={colors.PROFILE_PLACEHOLDER_CONTENT}
                                    size={25}
                                />

                            </View>
                            <View style={[styles.flexView1, [isRTL ? { marginRight: 15 } : null]]}>
                                <Text style={styles.text1}>{t('referralId')}</Text>
                                <Text style={styles.text2}>{profileData.referralId}</Text>
                            </View>
                            <TouchableOpacity
                                style={[isRTL ? { marginRight: 10, marginTop: 15 } : { marginLeft: 10, marginTop: 15 }]}
                                onPress={() => {
                                    settings.bonus > 0 ?
                                        Share.share({
                                            message: t('share_msg') + settings.code + ' ' + settings.bonus + ".\n" + t('code_colon') + auth.profile.referralId + "\n" + t('app_link') + (Platform.OS == "ios" ? settings.AppleStoreLink : settings.PlayStoreLink)
                                        })
                                        :
                                        Share.share({
                                            message: t('share_msg_no_bonus') + "\n" + t('app_link') + (Platform.OS == "ios" ? settings.AppleStoreLink : settings.PlayStoreLink)
                                        })
                                }}
                            >
                                <Icon
                                    name={Platform.OS == 'android' ? 'share-social' : 'share'}
                                    type='ionicon'
                                    color={colors.INDICATOR_BLUE}
                                />
                            </TouchableOpacity>
                        </View>
                        : null}
                    <View style={[styles.myViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                        <View style={[styles.iconViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                            <Icon
                                name='user'
                                type='simple-line-icon'
                                color={colors.PROFILE_PLACEHOLDER_CONTENT}
                                size={25}
                            />

                        </View>
                        <View style={[styles.flexView1, [isRTL ? { marginRight: 15 } : null]]}>
                            <Text style={styles.text1}>{t('usertype')}</Text>
                            <Text style={styles.text2}>{profileData ? t(profileData.usertype) : ''}</Text>
                        </View>
                    </View>
                    {profileData && profileData.usertype == 'driver' ?
                        <View style={[styles.myViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                            <View style={[styles.iconViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row', }]}>
                                <Ionicons name="md-car-sport-outline" size={25} color={colors.PROFILE_PLACEHOLDER_CONTENT} />
                            </View>
                            <View style={[styles.flexView1, [isRTL ? { marginRight: 15 } : null]]}>
                                <Text style={styles.text1}>{t('car_type')}</Text>
                                <Text style={styles.text2}>{profileData.carType}</Text>
                            </View>
                        </View>
                        : null}
                    {profileData && profileData.usertype == 'driver' ?
                        <View style={[styles.myViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                            <View style={[styles.iconViewStyle, { flexDirection: isRTL ? 'row-reverse' : 'row', }]}>
                                <MaterialCommunityIcons name="star-shooting-outline" size={25} color={colors.PROFILE_PLACEHOLDER_CONTENT} />
                            </View>
                            <View style={[styles.flexView1, [isRTL ? { marginRight: 15 } : null]]}>
                                <Text style={styles.text1}>{t('you_rated_text')}</Text>
                                <View style={[{ flex: 1 }, isRTL ? { alignSelf: 'flex-end', flexDirection: 'row-reverse' } : { alignSelf: 'flex-start', flexDirection: 'row' }]}>
                                    <Text style={[styles.text2, isRTL ? { color: colors.ProfileDetails_Primary } : { left: 10, color: colors.ProfileDetails_Primary }]}>{profileData && profileData.usertype && profileData.rating ? profileData.rating : 0}</Text>
                                    <StarRating
                                        disabled={false}
                                        maxStars={5}
                                        starSize={15}
                                        fullStar={'ios-star'}
                                        halfStar={'ios-star-half'}
                                        emptyStar={'ios-star-outline'}
                                        iconSet={'Ionicons'}
                                        fullStarColor={colors.STAR}
                                        emptyStarColor={colors.STAR}
                                        halfStarColor={colors.STAR}
                                        rating={profileData && profileData.usertype && profileData.rating ? parseFloat(profileData.rating) : 0}
                                        containerStyle={[styles.contStyle, isRTL ? { marginRight: 10 } : { marginLeft: 10 }]}
                                    />
                                </View>
                            </View>
                        </View>
                        : null}
                </View>

                <TouchableOpacity onPress={deleteAccount} style={styles.vew2}>
                    <Text style={[styles.emailStyle, { color: colors.WHITE }]}>{t('delete_account_lebel')}</Text>
                </TouchableOpacity>

            </ScrollView>

        </View>
    );
}

const styles = StyleSheet.create({
    headerStyle: {
        // backgroundColor: colors.HEADER,

    },
    headerTitleStyle: {
        color: colors.HEADER,
        fontFamily: 'Roboto-Bold',
        fontSize: 20
    },
    logo: {
        flex: 1,
        position: 'absolute',
        top: 110,
        width: '100%',
        justifyContent: "flex-end",
        alignItems: 'center'
    },
    footer: {
        flex: 1,
        position: 'absolute',
        bottom: 0,
        height: 150,
        width: '100%',
        flexDirection: 'row',
        justifyContent: 'space-around',
        alignItems: 'center'
    },
    vew2: {
        flexDirection: 'row',
        height: 40, 
        width: 140,
        alignItems: 'center', 
        justifyContent: 'center',
        alignSelf: 'center',
        backgroundColor: colors.LIGHT_RED,
        borderRadius: 10,
        marginVertical:15
    },
    scrollStyle: {
        height: height,
        backgroundColor: colors.WHITE,
        marginTop: 90,
    },
    scrollViewStyle: {
        width: width,
        height: 50,
        marginVertical: 10,
        backgroundColor: colors.BACKGROUND_PRIMARY,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between'
    },
    profStyle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: colors.PROFILE_PLACEHOLDER_CONTENT,
        fontFamily: 'Roboto-Bold'
    },
    bonusAmount: {
        right: 20,
        fontSize: 16,
        fontWeight: 'bold'
    },
    viewStyle: {
        // justifyContent: 'center',
        // alignItems: 'center',
        // flexDirection: 'row',
        alignItems: 'center',
        height: '20%',
    },
    vew1: {
        backgroundColor: colors.WHITE,
        height: 130,
        width: '100%',
        marginTop: 50,
        // shadowColor: "#000",
        // shadowOffset: {
        //     width: 0,
        //     height: 6,
        // },
        // shadowOpacity: 0.37,
        // shadowRadius: 7.49,
        // elevation: 3,
        // borderRadius:15,
        borderTopLeftRadius: 40,
        borderTopRightRadius: 40
    },
    imageViewStyle: {
        backgroundColor: colors.WHITE,
        shadowColor: "#000",
        shadowOffset: {
            width: 0,
            height: 5,
        },
        shadowOpacity: 0.2,
        shadowRadius: 3,
        elevation: 3,
        width: 100,
        height: 100,
        alignSelf: 'center',
        borderRadius: 100 / 2,
        marginTop: -40,
        overflow: 'hidden',
        justifyContent: 'center'
    },
    textPropStyle: {
        fontSize: 21,
        fontWeight: 'bold',
        color: colors.BUTTON,
        fontFamily: 'Roboto-Bold',
        textTransform: 'uppercase'
    },
    newViewStyle: {
        flex: 1,
        marginTop: 10,
        height: '50%'
    },
    myViewStyle: {
        flex: 1,
        borderBottomColor: colors.BORDER_TEXT,
        marginHorizontal: 10,
        backgroundColor: colors.WHITE,
        shadowColor: "#000",
        shadowOffset: {
            width: 0,
            height: 4,
        },
        shadowOpacity: 0.2,
        shadowRadius: 3,
        elevation: 2,
        marginBottom: 10,
        height: 54,
        borderRadius: 15,
    },
    iconViewStyle: {
        alignSelf: 'center',
        padding: 10,
    },
    emailStyle: {
        fontSize: 17,
        color: colors.BLACK,
        fontFamily: 'Roboto-Bold',
        textAlign: 'center'
    },
    emailAdressStyle: {
        fontSize: 15,
        color: colors.PROFILE_PLACEHOLDER_CONTENT,
        fontFamily: 'Roboto-Regular'
    },
    mainIconView: {
        flex: 1,
        left: 20,
        marginRight: 40,
        borderBottomColor: colors.BUTTON,
        borderBottomWidth: 1
    },
    text1: {
        fontSize: 17,
        left: 10,
        color: colors.PROFILE_PLACEHOLDER_CONTENT,
        fontFamily: 'Roboto-Bold'
    },
    text2: {
        fontSize: 15,
        left: 10,
        color: colors.PROFILE_PLACEHOLDER_CONTENT,
        fontFamily: 'Roboto-Regular'
    },
    textIconStyle: {
        width: width,
        height: 50,
        backgroundColor: colors.BACKGROUND_PRIMARY,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between'
    },
    textIconStyle2: {
        width: width,
        height: 50,
        marginTop: 10,
        backgroundColor: colors.BACKGROUND_PRIMARY,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between'
    },
    mainView: {
        flex: 1,
        backgroundColor: colors.WHITE,

        //marginTop: StatusBar.currentHeight 
    },
    flexView1: {
        padding: 3
    },

    flexView3: {
        marginTop: 10,
        marginBottom: 10
    },
    loadingcontainer: {
        flex: 1,
        justifyContent: 'center'
    },
    horizontal: {
        flexDirection: 'row',
        justifyContent: 'space-around',
        padding: 10
    },
    contStyle: {
        width: 90,
    },
    pickerStyle: {
        color: colors.HEADER,
        width: 60,
        fontSize: 15,
        height: 30,
        fontWeight: 'bold'

    },
    pickerStyle1: {
        color: colors.HEADER,
        width: 68,
        fontSize: 15,
        height: 30,
        fontWeight: 'bold'

    },
    vew: {
        width: '40%',
        height: 65
    }
});