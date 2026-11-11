import React from 'react';
import { StyleSheet, Text, View, TouchableOpacity, SafeAreaView } from 'react-native';

export default function HomeScreen({ route, navigation }) {
  const { userName } = route.params || { userName: 'User' };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.content}>
        <Text style={styles.welcomeText}>Welcome to</Text>
        <Text style={styles.logoText}>Ab<Text style={styles.redExclamation}>!</Text>SU</Text>
        <Text style={styles.nameText}>Hello, {userName}!</Text>

        <TouchableOpacity 
          style={styles.logoutButton} 
          onPress={() => navigation.navigate('Landing')}
        >
          <Text style={styles.buttonText}>LOG OUT</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', paddingHorizontal: 25 },
  content: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  welcomeText: { fontSize: 18, color: '#555' },
  logoText: { fontSize: 48, fontWeight: 'bold', marginBottom: 10, letterSpacing: 1, color: '#000' },
  redExclamation: { color: '#D0021B' },
  nameText: { fontSize: 22, fontWeight: '600', color: '#333', marginBottom: 40 },
  logoutButton: { backgroundColor: '#D0021B', paddingVertical: 14, paddingHorizontal: 40, borderRadius: 2 },
  buttonText: { color: '#FFFFFF', fontWeight: 'bold', fontSize: 16, letterSpacing: 1 }
});