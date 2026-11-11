import React, { useState } from 'react';
import { StyleSheet, Text, View, TextInput, TouchableOpacity, SafeAreaView, Alert } from 'react-native';
import { usersDatabase } from '../mockUsers';

export default function SignUpScreen({ navigation }) {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const handleSignUp = () => {
    if (!name || !email || !password || !confirmPassword) {
      Alert.alert("Error", "Please fill in all fields.");
      return;
    }

    if (password !== confirmPassword) {
      Alert.alert("Error", "Passwords do not match!");
      return;
    }

    const existingUser = usersDatabase.find(u => u.email.toLowerCase() === email.toLowerCase());
    if (existingUser) {
      Alert.alert("Error", "An account with this email already exists!");
      return;
    }

    usersDatabase.push({ name, email, password });
    
    Alert.alert("Success!", "Account created successfully! You can now log in.");
    navigation.navigate('Login');
  };

  return (
    <SafeAreaView style={styles.container}>
      <Text style={styles.headerTitle}>SIGN UP</Text>

      <View style={styles.content}>
        <Text style={styles.logoText}>Ab<Text style={styles.redExclamation}>!</Text>SU</Text>

        <View style={styles.form}>
          <TextInput
            style={styles.input}
            placeholder="Enter name"
            placeholderTextColor="#888"
            value={name}
            onChangeText={setName}
          />
          <TextInput
            style={styles.input}
            placeholder="Enter email"
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
          <TextInput
            style={styles.input}
            placeholder="Confirm password"
            placeholderTextColor="#888"
            secureTextEntry
            value={confirmPassword}
            onChangeText={setConfirmPassword}
          />

          <TouchableOpacity style={styles.button} onPress={handleSignUp}>
            <Text style={styles.buttonText}>SIGN UP</Text>
          </TouchableOpacity>
        </View>

        <TouchableOpacity onPress={() => navigation.navigate('Login')}>
          <Text style={styles.footerText}>Already have an account ? <Text style={styles.linkText}>Log in</Text></Text>
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
  button: { backgroundColor: '#D0021B', paddingVertical: 16, alignItems: 'center', borderRadius: 2, marginTop: 15 },
  buttonText: { color: '#FFFFFF', fontWeight: 'bold', fontSize: 16, letterSpacing: 1 },
  footerText: { textAlign: 'center', marginTop: 25, color: '#333', fontSize: 13 },
  linkText: { fontWeight: 'bold', color: '#000' }
});