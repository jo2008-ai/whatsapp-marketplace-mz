import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';

const ESTADOS = {
  pendente: { label: 'Pendente', color: '#f59e0b', bg: '#fffbeb' },
  confirmada: { label: 'Confirmada', color: '#3b82f6', bg: '#eff6ff' },
  entregue: { label: 'Entregue', color: '#10b981', bg: '#ecfdf5' },
  cancelada: { label: 'Cancelada', color: '#ef4444', bg: '#fef2f2' },
};

export default function EncomendaCard({ encomenda, onProximoEstado }) {
  const estado = ESTADOS[encomenda.estado] || ESTADOS.pendente;
  const proximo =
    encomenda.estado === 'pendente'
      ? { estado: 'confirmada', label: 'Confirmar' }
      : encomenda.estado === 'confirmada'
      ? { estado: 'entregue', label: 'Entregue' }
      : null;

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <Text style={styles.cliente}>{encomenda.cliente || encomenda.numero_cliente}</Text>
        <View style={[styles.badge, { backgroundColor: estado.bg }]}>
          <Text style={[styles.badgeText, { color: estado.color }]}>{estado.label}</Text>
        </View>
      </View>
      <Text style={styles.produto}>{encomenda.produto?.nome || 'Produto'}</Text>
      <View style={styles.footer}>
        <Text style={styles.total}>{Number(encomenda.preco_total || encomenda.total || 0).toLocaleString('pt-MZ')} MZN</Text>
        <Text style={styles.data}>{encomenda.data || encomenda.created_at?.substring(0, 10)}</Text>
      </View>
      {proximo && (
        <TouchableOpacity
          onPress={() => onProximoEstado(encomenda.id, proximo.estado)}
          style={[styles.btn, { backgroundColor: proximo.estado === 'confirmada' ? '#3b82f6' : '#10b981' }]}
        >
          <Text style={styles.btnText}>{proximo.label}</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 14,
    marginHorizontal: 16,
    marginVertical: 4,
    elevation: 1,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  cliente: {
    fontSize: 15,
    fontWeight: '600',
    color: '#1f2937',
  },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '600',
  },
  produto: {
    fontSize: 13,
    color: '#6b7280',
    marginTop: 4,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
  },
  total: {
    fontSize: 14,
    fontWeight: '600',
    color: '#2563EB',
  },
  data: {
    fontSize: 12,
    color: '#9ca3af',
  },
  btn: {
    marginTop: 10,
    paddingVertical: 8,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnText: {
    color: '#fff',
    fontWeight: '600',
    fontSize: 13,
  },
});
