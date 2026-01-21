import React, { useState,useContext } from 'react';
import {
    StyleSheet,
    View,
    Text,
} from 'react-native';
import { colors } from '../common/theme';
import { MAIN_COLOR } from '../common/sharedFunctions';

export default function Footer(props) {
return (
    <View style={{ backgroundColor: colors.WHITE, height: '20%'}}>
    <View style={[styles.vew, { backgroundColor: MAIN_COLOR }]}>
    </View>
</View>

);
}

const styles = StyleSheet.create({
    vew: {
        borderTopLeftRadius: 130,
        height: '100%',
        alignItems: 'flex-end'
    },
})