import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import { Text, View, TouchableOpacity, StyleSheet } from 'react-native';

import { useAuth } from '../context/AuthContext';
import LoginScreen from '../screens/LoginScreen';
import DashboardScreen from '../screens/DashboardScreen';
import ProdutosScreen from '../screens/ProdutosScreen';
import NovoProdutoScreen from '../screens/NovoProdutoScreen';
import EditarProdutoScreen from '../screens/EditarProdutoScreen';
import EncomendasScreen from '../screens/EncomendasScreen';
import EncomendaDetalheScreen from '../screens/EncomendaDetalheScreen';
import CategoriasScreen from '../screens/CategoriasScreen';
import VendedoresScreen from '../screens/VendedoresScreen';
import LoadingOverlay from '../components/LoadingOverlay';

const Tab = createBottomTabNavigator();
const Stack = createStackNavigator();

function TabIcon({ label, focused }) {
  const icons = {
    Dashboard: '📊',
    Produtos: '📦',
    Mais: '⋯',
  };
  return (
    <Text style={{ fontSize: focused ? 20 : 18, opacity: focused ? 1 : 0.5 }}>
      {icons[label] || '📌'}
    </Text>
  );
}

function ProdutosStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="ProdutosLista" component={ProdutosScreen} />
      <Stack.Screen name="NovoProduto" component={NovoProdutoScreen} options={{ headerShown: true, title: 'Novo Produto' }} />
      <Stack.Screen name="EditarProduto" component={EditarProdutoScreen} options={{ headerShown: true, title: 'Editar Produto' }} />
    </Stack.Navigator>
  );
}

function MaisStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="MaisMenu" component={MaisMenuScreen} />
      <Stack.Screen name="Encomendas" component={EncomendasScreen} options={{ headerShown: true, title: 'Encomendas' }} />
      <Stack.Screen name="EncomendaDetalhe" component={EncomendaDetalheScreen} options={{ headerShown: true, title: 'Encomenda' }} />
      <Stack.Screen name="Categorias" component={CategoriasScreen} options={{ headerShown: true, title: 'Categorias' }} />
      <Stack.Screen name="Vendedores" component={VendedoresScreen} options={{ headerShown: true, title: 'Vendedores' }} />
    </Stack.Navigator>
  );
}

function MaisMenuScreen({ navigation }) {
  const items = [
    { label: 'Encomendas', icon: '📋', screen: 'Encomendas' },
    { label: 'Categorias', icon: '🏷️', screen: 'Categorias' },
    { label: 'Vendedores', icon: '👥', screen: 'Vendedores' },
  ];

  return (
    <View style={menuStyles.container}>
      {items.map((item) => (
        <TouchableOpacity
          key={item.screen}
          onPress={() => navigation.navigate(item.screen)}
          style={menuStyles.item}
        >
          <Text style={menuStyles.icon}>{item.icon}</Text>
          <Text style={menuStyles.label}>{item.label}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

const menuStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f3f4f6',
    padding: 16,
  },
  item: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  icon: {
    fontSize: 24,
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
  },
});

function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarIcon: ({ focused }) => <TabIcon label={route.name} focused={focused} />,
        tabBarActiveTintColor: '#2563EB',
        tabBarInactiveTintColor: '#9ca3af',
        tabBarStyle: {
          backgroundColor: '#fff',
          borderTopWidth: 1,
          borderTopColor: '#f3f4f6',
          paddingBottom: 4,
          height: 56,
        },
        tabBarLabelStyle: { fontSize: 11, fontWeight: '500' },
      })}
    >
      <Tab.Screen name="Dashboard" component={DashboardScreen} />
      <Tab.Screen name="Produtos" component={ProdutosStack} />
      <Tab.Screen name="Mais" component={MaisStack} />
    </Tab.Navigator>
  );
}

export default function AppNavigator() {
  const { user, loading } = useAuth();

  if (loading) return <LoadingOverlay />;

  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      {user ? (
        <Stack.Screen name="Main" component={MainTabs} />
      ) : (
        <Stack.Screen name="Login" component={LoginScreen} />
      )}
    </Stack.Navigator>
  );
}
