import React, { useEffect, useState } from 'react';
import { WTransactionHistory } from '../components';
import {
  StyleSheet,
  View,
  Text,
  Dimensions,
  Alert
} from 'react-native';
import { Icon } from 'react-native-elements';
import { colors } from '../common/theme';
var { height } = Dimensions.get('window');
import i18n from 'i18n-js';
import { useSelector } from 'react-redux';
import { TouchableOpacity } from 'react-native-gesture-handler';
import { CommonActions } from '@react-navigation/native';
import { MAIN_COLOR } from '../common/sharedFunctions';
export default function WalletDetails(props) {

  const auth = useSelector(state => state.auth);
  const walletHistory = useSelector(state => state.auth.walletHistory);
  const settings = useSelector(state => state.settingsdata.settings);
  const providers = useSelector(state => state.paymentmethods.providers);
  const [profile, setProfile] = useState();
  const { t } = i18n;
  const isRTL = i18n.locale.indexOf('he') === 0 || i18n.locale.indexOf('ar') === 0;

  useEffect(() => {
    if (auth.profile && auth.profile.uid) {
      setProfile(auth.profile);
    } else {
      setProfile(null);
    }
  }, [auth.profile]);

  const doReacharge = () => {
    if (!(profile.mobile && profile.mobile.length > 6 && profile.email && profile.firstName)) {
      Alert.alert(t('alert'), t('profile_incomplete'));
      props.navigation.dispatch(CommonActions.reset({ index: 0, routes:[{ name: 'editUser', params: { fromPage: 'Wallet'}}]}));
    } else {
      if (providers) {
        props.navigation.push('addMoney', { userdata: profile, providers: providers });
      } else {
        Alert.alert(t('alert'), t('provider_not_found'))
      }
    }
  }

  const doWithdraw = () => {
    if (!(profile.mobile && profile.mobile.length > 6) && profile.email && profile.firstName) {
      Alert.alert(t('alert'), t('profile_incomplete'));
      props.navigation.dispatch(CommonActions.reset({ index: 0, routes:[{ name: 'editUser', params: { fromPage: 'Wallet'}}]}));
    } else {
      if (parseFloat(profile.walletBalance) > 0) {
        props.navigation.push('withdrawMoney', { userdata: profile });
      } else {
        Alert.alert(t('alert'), t('wallet_zero'))
      }
    }
  }

  const newData = ({ item }) => {
    return (
        <View style={styles.container}>

            <View style={[styles.divCompView, { flexDirection: isRTL ? 'row-reverse' : 'row',backgroundColor: item.type == 'Credit' ? colors.new1 : (item.type == 'Debit' ? colors.new2 :'#f1c8b7')}]}>
                <View style={styles.containsView}>
                    <View style={[styles.statusStyle, { flexDirection: isRTL ? 'row-reverse' : 'row' }]}>
                        {item.type == 'Credit' ?
                            <View style={[styles.icon, isRTL ? { marginRight: 10 } : { marginLeft: 10 }]}>
                                <Icon
                                    iconStyle={styles.debiticonPositionStyle}
                                    name={isRTL ? 'keyboard-arrow-right' : 'keyboard-arrow-left'}
                                    type='MaterialIcons'
                                    size={32}
                                    color={colors.HEADER}
                                />

                            </View>
                            : null}
                        {item.type == 'Debit' ?
                            <View style={[styles.icon, isRTL ? { marginRight: 10 } : { marginLeft: 10 }]}>
                                <Icon
                                    iconStyle={styles.crediticonPositionStyle}
                                    name={isRTL ? 'keyboard-arrow-left' : 'keyboard-arrow-right'}
                                    type='MaterialIcons'
                                    size={32}
                                    color={colors.HEADER}
                                />
                            </View>
                            : null}
                        {item.type == 'Withdraw' ?
                            <View style={[styles.icon, isRTL ? { marginRight: 10 } : { marginLeft: 10 }]}>
                                <Icon
                                    iconStyle={styles.crediticonPositionStyle}
                                    name='keyboard-arrow-down'
                                    type='MaterialIcons'
                                    size={32}
                                    color={colors.HEADER}
                                />
                            </View>
                            : null}
                        <View style={[styles.statusView, isRTL ? { marginRight: 10 } : { marginLeft: 10 }]}>
                            {item.type && item.type == 'Credit' ?
                                settings.swipe_symbol === false ?
                                    <Text style={[styles.historyamounttextStyle, { textAlign: isRTL ? "right" : "left" }]}>{t('credited') + ' ' + settings.symbol + parseFloat(item.amount).toFixed(settings.decimal)}</Text>
                                    :
                                    <Text style={[styles.historyamounttextStyle, { textAlign: isRTL ? "right" : "left" }]}>{t('credited') + ' ' + parseFloat(item.amount).toFixed(settings.decimal) + settings.symbol}</Text>
                                : null}
                            {item.type && item.type == 'Debit' ?
                                settings.swipe_symbol === false ?
                                    <Text style={[styles.historyamounttextStyle, { textAlign: isRTL ? "right" : "left" }]}>{t('debited') + ' ' + settings.symbol + parseFloat(item.amount).toFixed(settings.decimal)}</Text>
                                    :
                                    <Text style={[styles.historyamounttextStyle, { textAlign: isRTL ? "right" : "left" }]}>{t('debited') + ' ' + parseFloat(item.amount).toFixed(settings.decimal) + settings.symbol}</Text>
                                : null}
                            {item.type && item.type == 'Withdraw' ?
                                settings.swipe_symbol === false ?
                                    <Text style={[styles.historyamounttextStyle, { textAlign: isRTL ? "right" : "left" }]}>{t('withdrawn') + ' ' + settings.symbol + parseFloat(item.amount).toFixed(settings.decimal)}</Text>
                                    :
                                    <Text style={[styles.historyamounttextStyle, { textAlign: isRTL ? "right" : "left" }]}>{t('withdrawn') + ' ' + parseFloat(item.amount).toFixed(settings.decimal) + settings.symbol}</Text>
                                : null}
                            <Text style={[styles.textStyle2, { textAlign: isRTL ? "right" : "left",  fontWeight: '500', }]}>{t('transaction_id')} {item.txRef.startsWith('wallet') ? item.transaction_id : item.txRef}</Text>
                            <Text style={[styles.textStyle2, { textAlign: isRTL ? "right" : "left" }]}>{item.date}</Text>
                        </View>
                    </View>
                </View>
            </View>

        </View>
    )
}


  const walletBar = height / 4;
  return (
    <View style={styles.mainView}>
      <View style={styles.Vew}>

        <View style={[styles.View6, {backgroundColor: MAIN_COLOR }]}>
          {settings.swipe_symbol === false ?
            <Text style={{ textAlign: 'center', fontSize: 30, fontWeight: '500', color: colors.WHITE }}>{settings.symbol}{profile && profile.hasOwnProperty('walletBalance') ? parseFloat(profile.walletBalance).toFixed(settings.decimal) : ''}</Text>
            :
            <Text style={{ textAlign: 'center', fontSize: 30, fontWeight: '500', color: colors.WHITE }}>{profile && profile.hasOwnProperty('walletBalance') ? parseFloat(profile.walletBalance).toFixed(settings.decimal) : ''}{settings.symbol}</Text>
          }
          {profile && (profile.usertype == 'driver' || (profile.usertype == 'customer' && settings && settings.RiderWithDraw)) ?
          <View style={{flexDirection:'row',justifyContent:'space-around',marginVertical:15,width:'100%',height:50}}>
            <View style={[styles.Vew1]}>
            <TouchableOpacity onPress={doReacharge} style={styles.vew7}>
            <Text style={[styles.txt,{color:colors.DRIVER_TRIPS_BUTTON}]}>{t('add_money')}</Text>
            </TouchableOpacity>
            </View>
            <View style={styles.Vew1}>
            <TouchableOpacity onPress={doWithdraw} style={styles.vew7}>
            <Text style={[styles.txt,{color:colors.new2}]}>{t('withdraw')}</Text>
            </TouchableOpacity>
            </View>
          </View>
           :
          <View style={{flexDirection:'row',justifyContent:'center',marginVertical:15,width:'100%',height:50}}>
          <View style={styles.Vew1}>
          <TouchableOpacity onPress={doReacharge} style={styles.vew7}>
          <Text style={[styles.txt,{color:colors.DRIVER_TRIPS_BUTTON}]}>{t('add_money')}</Text>
          </TouchableOpacity>
          </View>
          </View>
        } 
        </View>
      </View>
    
      <View style={styles.Vew4}>
        <Text style={[styles.View5, { textAlign: isRTL ? "right" : "left" }]}>{t('transaction_history_title')}</Text>
        <WTransactionHistory walletHistory={walletHistory ? walletHistory : []} role={auth.profile && auth.profile.usertype? auth.profile.usertype:null}/>
      </View>

    </View>

  );

}

const styles = StyleSheet.create({
  mainView: {
    flex: 1,
    backgroundColor: colors.WHITE,
  },
  Vew4: {
    flex: 1,
    marginTop: 5,
    borderTopRightRadius: 20,
    borderTopLeftRadius: 20,
    padding: 5,
    backgroundColor: colors.WHITE
  },
  Vew1: {
    height:'100%',
    width:120,
    borderRadius:25,
    backgroundColor: colors.WHITE,
    shadowColor: "#000",
    shadowOffset: {
      width: 0,
      height: 5,
    },
    shadowOpacity: 0.2,
    shadowRadius: 3,
    elevation: 3,
    // alignItems: 'center',
  },
  Vew: {
    width: '100%',
    height: 'auto',
    backgroundColor: colors.WHITE,
  },
  View5: {
    paddingHorizontal: 10,
    fontSize: 18,
    fontWeight: '500',
    marginTop: 10
  },
  View6: {
    width: '100%',
    //height: '100%',
    alignSelf: 'flex-end',
    // backgroundColor: colors.RE_GREEN,
    // borderBottomLeftRadius: 30,
    justifyContent: 'center',
    shadowColor: "#000",
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.4,
    shadowRadius: 2,
    elevation: 6,
    borderBottomRightRadius: 20,
    borderBottomLeftRadius: 20
  },
  txt: { 
    textAlign: 'center',
    fontSize: 18,
    color: colors.HEADER,
 fontWeight:'bold',
  },
  vew7:{
    height:'100%',
    width:'100%',
    justifyContent:'center'
  }
});