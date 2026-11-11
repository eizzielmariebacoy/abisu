import React, { useState } from 'react';
import { StyleSheet, Text, View, TextInput, TouchableOpacity, SafeAreaView, Alert } from 'react-native';
import { usersDatabase } from '../mockUsers';

export default function LoginScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = () => {
    if (!email || !password) {
      Alert.alert("Error", "Please enter both email and password.");
      return;
    }

    const user = usersDatabase.find(u => u.email.toLowerCase() === email.toLowerCase() && u.password === password);

    if (!user) {
      Alert.alert("Login Failed", "Invalid email or password. Please sign up first if you don't have an account.");
    } else {
      Alert.alert("Success", `Welcome back, ${user.name}!`);
      navigation.navigate('Home', { userName: user.name });
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.headerTitle}>LOGIN</Text>

      <View style={styles.content}>
        <Text style={styles.logoText}>Ab<Text style={styles.redExclamation}>!</Text>SU</Text>

        <View style={styles.form}>
          <TextInput
            style={styles.input}
            placeholder="Enter email address"
            placeholderTextColor="#888"
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
          />
          <TextInput
            style={styles.input}
            placeholder="Enter password"
            placeholderTextColor="#888"
            secureTextEntry
            value={password}
            onChangeText={setPassword}
          />

          <TouchableOpacity style={styles.forgotContainer}>
            <Text style={styles.forgotText}>Forget Password ?</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.button} onPress={handleLogin}>
            <Text style={styles.buttonText}>LOG IN</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity onPress={() => navigation.navigate('SignUp')}>
          <Text style={styles.footerText}>Not registered yet ? <Text style={styles.linkText}>Sign up</Text></Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', paddingHorizontal: 25, paddingTop: 20 },
  headerTitle: { fontSize: 20, fontWeight: '400', letterSpacing: 0.5, color: '#000' },
  content: { flex: 1, justifyContent: 'center' },
  logoText: { fontSize: 44, fontWeight: 'bold', textAlign: 'center', marginBottom: 40, letterSpacing: 1 },
  redExclamation: { color: '#D0021B' },
  form: { width: '100%' },
  input: { borderWidth: 1, borderColor: '#BBB', padding: 14, borderRadius: 8, marginBottom: 15, fontSize: 14, color: '#000' },
  forgotContainer: { alignItems: 'flex-end', marginBottom: 20 },
  forgotText: { fontSize: 13, color: '#000', fontWeight: '500' },
  button: { backgroundColor: '#D0021B', paddingVertical: 16, alignItems: 'center', borderRadius: 2 },
  buttonText: { color: '#FFFFFF', fontWeight: 'bold', fontSize: 16, letterSpacing: 1 },
  footerText: { textAlign: 'center', marginTop: 25, color: '#333', fontSize: 13 },
  linkText: { fontWeight: 'bold', color: '#000' }
});