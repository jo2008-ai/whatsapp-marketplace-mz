import React from 'react';
import { View, Text, StyleSheet } from 'react-native';

export default function StatCard({ label, value, color = '#2563EB' }) {
  return (
    <View style={[styles.card, { borderLeftColor: color }]}>
      <Text style={styles.value}>{value}</Text>
      <Text style={styles.label}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    flex: 1,
    marginHorizontal: 4,
    borderLeftWidth: 4,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
  },
  value: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  label: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 4,
  },
});
