import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { COLORS } from '../constants/colors';

export default function MaisMenuScreen({ navigation }) {
  const items = [
    { label: 'Encomendas', icon: '📋', screen: 'Encomendas' },
    { label: 'Stock', icon: '📊', screen: 'Stock' },
    { label: 'Categorias', icon: '🏷️', screen: 'Categorias' },
    { label: 'Vendedores', icon: '👥', screen: 'Vendedores' },
  ];

  return (
    <View style={styles.container}>
      {items.map((item) => (
        <TouchableOpacity
          key={item.screen}
          onPress={() => navigation.navigate(item.screen)}
          style={styles.item}
        >
          <Text style={styles.icon}>{item.icon}</Text>
          <Text style={styles.label}>{item.label}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.bgGrayDark,
    padding: 16,
  },
  item: {
    backgroundColor: COLORS.bg,
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  icon: { fontSize: 24 },
  label: { fontSize: 16, fontWeight: '600', color: COLORS.text },
});
