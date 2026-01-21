import { React } from 'react';
import { View, Text, TouchableOpacity, Image } from 'react-native';
import { colors } from './theme';
import { Icon } from 'react-native-elements'
import i18n from 'i18n-js';
import { api } from 'common';
import DeliveryModal from '../components/DeliveryModal';

export const MAIN_COLOR = colors.BUTTON_ORANGE;
export const checkCat = (cat) => cat === 2;

export const checkSearchPhrase = (str) => {
    return str;
}

export const FormIcon = (props)=>{
    return  <Icon
        name='truck-fast'
        type='material-community'
        color={colors.HEADER}
        size={18}
        containerStyle={{width: '15%',alignItems: 'center'}}
    />
}

export const CarHorizontal = (props) => {
    const { t } = i18n;
    const isRTL = i18n.locale.indexOf('he') === 0 || i18n.locale.indexOf('ar') === 0;
    const {onPress, carData, settings, styles} = props;
    return (
        <TouchableOpacity onPress={onPress} style={{height:'100%'}}>
            <View style={styles.imageStyle}>
                <Image resizeMode="contain" source={carData.image ? { uri: carData.image } : require('../../assets/images/microBlackCar.png')} style={styles.imageStyle1} />
            </View>
            <View style={styles.textViewStyle}>
                <Text style={styles.text1}>{carData.name.toUpperCase()}</Text>
                <Text style={styles.text1}>{carData.name.toUpperCase()}</Text>
                {carData.extra_info && carData.extra_info != '' ?
                    <View style={{ justifyContent: 'space-around', flexDirection: 'column', alignItems: 'center',marginTop:5 }}>
                        {
                            carData.extra_info.split(',').map((ln) => <Text style={styles.text2} key={ln} >{ln}</Text>)
                        }
                    </View>
                : null}
                    <View style={{ flexDirection: 'row', alignItems: 'center', marginLeft: 10, marginTop:5 }}>
                        {isRTL ?
                            null :
                            settings.swipe_symbol === false ?
                                <Text style={[styles.text2, { fontWeight: 'bold', color: colors.MAP_TEXT }]}>{settings.symbol}{carData.rate_per_unit_distance} / {settings.convert_to_mile ? t('mile') : t('km')} </Text>
                                :
                                <Text style={[styles.text2, { fontWeight: 'bold', color: colors.MAP_TEXT }]}>{carData.rate_per_unit_distance}{settings.symbol} / {settings.convert_to_mile ? t('mile') : t('km')} </Text>

                        }
                        {isRTL ?
                            settings.swipe_symbol === false ?
                                <Text style={[styles.text2, { fontWeight: 'bold', color: colors.MAP_TEXT }]}>{settings.symbol}{carData.rate_per_unit_distance} / {settings.convert_to_mile ? t('mile') : t('km')} </Text>
                                :
                                <Text style={[styles.text2, { fontWeight: 'bold', color: colors.MAP_TEXT }]}>{carData.rate_per_unit_distance}{settings.symbol} / {settings.convert_to_mile ? t('mile') : t('km')} </Text>
                            : null}
                    </View>
                <View>
                    <Text style={styles.text2}>({carData.minTime != '' ? carData.minTime : t('not_available')})</Text>
                </View>
            </View>
        </TouchableOpacity>
    )
}

export const CarVertical = (props) =>{
    const { t } = i18n;
    const {onPress, carData, settings, styles} = props;
    return (
        <TouchableOpacity
            style={[styles.carContainer, { backgroundColor: carData.active == true ? colors.BOX_BG : colors.WHITE }]}
            onPress={onPress}
        >
            <Image
                source={carData.image ? { uri: carData.image } : require('../../assets/images/microBlackCar.png')}
                resizeMode="contain"
                style={styles.cardItemImagePlace}
            ></Image>
            <View style={[styles.bodyContent, { alignContent: 'center', flexDirection: 'column', justifyContent: 'center' }]}>
                <Text style={styles.titleStyles}>{carData.name.toUpperCase()}</Text>
                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                    {settings.swipe_symbol === false ?
                        <Text style={[styles.text2, { fontWeight: 'bold', color: colors.MAP_TEXT }]}>{settings.symbol}{carData.rate_per_unit_distance} / {settings.convert_to_mile ? t('mile') : t('km')} </Text>
                        :
                        <Text style={[styles.text2, { fontWeight: 'bold', color: colors.MAP_TEXT }]}>{carData.rate_per_unit_distance}{settings.symbol} / {settings.convert_to_mile ? t('mile') : t('km')} </Text>
                    }
                    {carData.extra_info && carData.extra_info != '' ?
                        <View style={{ justifyContent: 'space-around', marginLeft: 3 }}>
                            {
                                carData.extra_info.split().map((ln) => <Text key={ln} style={[styles.text2, { fontWeight: 'bold', color: colors.MAP_TEXT }]} >{ln}</Text>)
                            }
                        </View>
                    : null}
                </View>
                <Text style={styles.text2}>({carData.minTime != '' ? carData.minTime : t('not_available')})</Text>
            </View>
        </TouchableOpacity>
    )
}

export const validateBookingObj = async (t, addBookingObj, instructionData, settings, bookingType, roundTrip, tripInstructions, tripdata, drivers) => {
    const regx1 = /([0-9\s-]{7,})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$/;
    if (/\S/.test(instructionData.deliveryPerson) && regx1.test(instructionData.deliveryPersonPhone) && instructionData.deliveryPersonPhone && instructionData.deliveryPersonPhone.length > 6) {
      addBookingObj['instructionData'] = instructionData;
      return { addBookingObj };
    } else {
      return { error: true, msg : t('deliveryDetailMissing')}
    }
}

export default function BookingModal(props){
    return <DeliveryModal {...props} />
}

export const prepareEstimateObject =  async (tripdata, instructionData) => {
    const { t } = i18n;
    const {
        getDirectionsApi
    } = api;
    try {
        const startLoc = tripdata.pickup.lat + ',' + tripdata.pickup.lng;
        const destLoc = tripdata.drop.lat + ',' + tripdata.drop.lng;
        let routeDetails = await getDirectionsApi(startLoc, destLoc, null);
        const estimateObject = {
            pickup: { coords: { lat: tripdata.pickup.lat, lng: tripdata.pickup.lng }, description: tripdata.pickup.add },
            drop: { coords: { lat: tripdata.drop.lat, lng: tripdata.drop.lng }, description: tripdata.drop.add},
            carDetails: tripdata.carType,
            routeDetails: routeDetails,
            instructionData: instructionData
        };
        return { estimateObject };
    } catch (err) {
        return { error: true, msg : t('not_available')}
    }
}

export const ExtraInfo = (props) => {
    const { t } = i18n;
    const isRTL = i18n.locale.indexOf('he') === 0 || i18n.locale.indexOf('ar') === 0;
    const { item, styles } = props;
    return (
        <>
            <View style={[styles.textContainerStyle, {flexDirection: isRTL? 'row-reverse' : 'row'}]}>
                <Text style={styles.textHeading}>{t('parcel_type')} - </Text>
                <Text style={styles.textContent}>
                    {item && item.parcelTypeSelected? item.parcelTypeSelected.description : ''}
                </Text>
            </View>
            <View style={[styles.textContainerStyle, {flexDirection: isRTL? 'row-reverse' : 'row'}]}>
                <Text style={styles.textHeading}>{t('options')} - </Text>
                <Text style={styles.textContent}>
                    {item && item.optionSelected? item.optionSelected.description : ''}
                </Text>
            </View>
            <View style={[styles.textContainerStyle, {flexDirection: isRTL? 'row-reverse' : 'row'}]}>
                <Text style={styles.textHeading}>{t('pickUpInstructions')} - </Text>
                <Text style={styles.textContent}>
                    {item? item.pickUpInstructions : ''}
                </Text>
            </View>
            <View style={[styles.textContainerStyle, {flexDirection: isRTL? 'row-reverse' : 'row'}]}>
                <Text style={styles.textHeading}>{t('deliveryInstructions')} - </Text>
                <Text style={styles.textContent}>
                    {item? item.deliveryInstructions : ''}
                </Text>
            </View>
            <View style={[styles.textContainerStyle, {flexDirection: isRTL? 'row-reverse' : 'row'}]}>
                <Text style={styles.textHeading}>{t('payment_mode')} - </Text>
                <Text style={styles.textContent}>
                { item.booking_type_admin ? 'Cash' : item.payment_mode}
                </Text>
            </View>
        </>
    )
}

export const RateView = (props) => {
    const {settings, item, styles} = props;
    return (
        <View style={styles.rateViewStyle}>
            {settings.swipe_symbol === false ?
                <Text style={styles.rateViewTextStyle}>{settings.symbol}{item ? item.estimate > 0 ? parseFloat(item.estimate).toFixed(settings.decimal) : 0 : null}</Text>
                :
                <Text style={styles.rateViewTextStyle}>{item ? item.estimate > 0 ? parseFloat(item.estimate).toFixed(settings.decimal) : 0 : null}{settings.symbol}</Text>
            }
        </View>
    )
}
