import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity, SafeAreaView } from 'react-native';

export default function LandingScreen({ navigation }) {
  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.logoContainer}>
        <Text style={styles.logoText}>Ab<Text style={styles.redExclamation}>!</Text>SU</Text>
      </View>

      <View style={styles.buttonContainer}>
        <TouchableOpacity
          style={styles.loginButton}
          onPress={() => navigation.navigate('Login')}
        >
          <Text style={styles.buttonTextWhite}>LOG IN</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.signupButton}
          onPress={() => navigation.navigate('SignUp')}
        >
          <Text style={styles.buttonTextDark}>SIGN UP</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', justifyContent: 'space-between', paddingVertical: 50, paddingHorizontal: 25 },
  logoContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  logoText: { fontSize: 50, fontWeight: 'bold', letterSpacing: 1, color: '#000' },
  redExclamation: { color: '#D0021B' },
  buttonContainer: { width: '100%', gap: 15 },
  loginButton: { backgroundColor: '#D0021B', paddingVertical: 16, alignItems: 'center', borderRadius: 2 },
  signupButton: { backgroundColor: '#EAEAEA', paddingVertical: 16, alignItems: 'center', borderRadius: 2 },
  buttonTextWhite: { color: '#FFFFFF', fontWeight: 'bold', fontSize: 16, letterSpacing: 1 },
  buttonTextDark: { color: '#000000', fontWeight: 'bold', fontSize: 16, letterSpacing: 1 }
});