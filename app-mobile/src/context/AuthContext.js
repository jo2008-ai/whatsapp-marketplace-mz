import React, { createContext, useState, useEffect, useContext } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { authApi } from '../api/endpoints';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  async function checkAuth() {
    try {
      const token = await AsyncStorage.getItem('token');
      if (!token) {
        setLoading(false);
        return;
      }
      const res = await authApi.me();
      if (res.data.success) {
        setUser(res.data.data);
      } else {
        await AsyncStorage.removeItem('token');
      }
    } catch {
      await AsyncStorage.removeItem('token');
    }
    setLoading(false);
  }

  async function login(email, password) {
    const res = await authApi.login(email, password);
    if (res.data.success) {
      await AsyncStorage.setItem('token', res.data.data.token);
      setUser(res.data.data.user);
      return { success: true };
    }
    return { success: false, message: res.data.message };
  }

  async function logout() {
    try {
      await authApi.logout();
    } catch {
      // ignora erro
    }
    await AsyncStorage.removeItem('token');
    setUser(null);
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be inside AuthProvider');
  return context;
}
