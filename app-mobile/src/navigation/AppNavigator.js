import React from 'react';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createStackNavigator } from '@react-navigation/stack';
import { Text } from 'react-native';

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
      <Stack.Screen name="EncomendaDetalhe" component={EncomendaDetalheScreen} options={{ headerShown: true, title: 'Encomenda' }} />
      <Stack.Screen name="Categorias" component={CategoriasScreen} options={{ headerShown: true, title: 'Categorias' }} />
      <Stack.Screen name="Vendedores" component={VendedoresScreen} options={{ headerShown: true, title: 'Vendedores' }} />
    </Stack.Navigator>
  );
}

function MaisMenuScreen({ navigation }) {
  const items = [
    { label: 'Categorias', icon: '🏷️', screen: 'Categorias' },
    { label: 'Vendedores', icon: '👥', screen: 'Vendedores' },
  ];

  return (
    <div style={{ flex: 1, backgroundColor: '#f3f4f6', padding: 16 }}>
      {items.map((item) => (
        <button
          key={item.screen}
          onClick={() => navigation.navigate(item.screen)}
          style={{
            backgroundColor: '#fff',
            borderRadius: 12,
            padding: 16,
            marginBottom: 12,
            display: 'flex',
            alignItems: 'center',
            gap: 12,
            border: 'none',
            cursor: 'pointer',
            width: '100%',
            textAlign: 'left',
          }}
        >
          <span style={{ fontSize: 24 }}>{item.icon}</span>
          <span style={{ fontSize: 16, fontWeight: '600', color: '#1f2937' }}>{item.label}</span>
        </button>
      ))}
    </div>
  );
}

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
