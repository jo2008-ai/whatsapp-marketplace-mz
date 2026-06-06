import React from 'react';
import { View, Text, StyleSheet, Alert, TouchableOpacity } from 'react-native';
import { encomendaApi } from '../api/endpoints';

const estadoColors = {
  pendente: { label: 'Pendente', bg: '#fffbeb', text: '#f59e0b' },
  confirmada: { label: 'Confirmada', bg: '#eff6ff', text: '#3b82f6' },
  entregue: { label: 'Entregue', bg: '#ecfdf5', text: '#10b981' },
  cancelada: { label: 'Cancelada', bg: '#fef2f2', text: '#ef4444' },
};

export default function EncomendaDetalheScreen({ route, navigation }) {
  const { encomenda } = route.params;
  const estado = estadoColors[encomenda.estado] || estadoColors.pendente;

  const handleCancelar = () => {
    Alert.alert(
      'Confirmar cancelamento',
      `Tens a certeza que desejas cancelar esta encomenda?\n\n${encomenda.produto?.nome} — ${encomenda.preco_total || encomenda.total} MZN`,
      [
        { text: 'Não', style: 'cancel' },
        {
          text: 'Sim, cancelar',
          style: 'destructive',
          onPress: async () => {
            try {
              await encomendaApi.updateEstado(encomenda.id, 'cancelada');
              Alert.alert('Sucesso', 'Encomenda cancelada.', [
                { text: 'OK', onPress: () => navigation.goBack() },
              ]);
            } catch (err) {
              Alert.alert('Erro', err.response?.data?.message || 'Não foi possível cancelar.');
            }
          },
        },
      ]
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.card}>
        <View style={styles.header}>
          <Text style={styles.id}>Encomenda #{encomenda.id}</Text>
          <View style={[styles.badge, { backgroundColor: estado.bg }]}>
            <Text style={[styles.badgeText, { color: estado.text }]}>{estado.label}</Text>
          </View>
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Cliente</Text>
          <Text style={styles.value}>{encomenda.cliente || encomenda.nome_cliente || encomenda.numero_cliente}</Text>
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Número</Text>
          <Text style={styles.value}>{encomenda.numero_cliente}</Text>
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Produto</Text>
          <Text style={styles.value}>{encomenda.produto?.nome || 'Produto'}</Text>
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Quantidade</Text>
          <Text style={styles.value}>{encomenda.quantidade || 1}</Text>
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Total</Text>
          <Text style={[styles.value, { color: '#2563EB', fontWeight: '700' }]}>
            {Number(encomenda.preco_total || encomenda.total).toLocaleString('pt-MZ')} MZN
          </Text>
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Data</Text>
          <Text style={styles.value}>{encomenda.data || encomenda.created_at?.substring(0, 10)}</Text>
        </View>

        {encomenda.vendedor && (
          <View style={styles.field}>
            <Text style={styles.label}>Vendedor</Text>
            <Text style={styles.value}>{encomenda.vendedor.nome}</Text>
          </View>
        )}
      </View>

      {encomenda.estado === 'pendente' && (
        <TouchableOpacity style={styles.cancelButton} onPress={handleCancelar}>
          <Text style={styles.cancelButtonText}>Cancelar Encomenda</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f3f4f6', padding: 16 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 16,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#f3f4f6',
  },
  id: { fontSize: 18, fontWeight: '700', color: '#1f2937' },
  badge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  badgeText: { fontSize: 12, fontWeight: '600' },
  field: { marginBottom: 12 },
  label: { fontSize: 12, color: '#9ca3af', marginBottom: 2 },
  value: { fontSize: 15, color: '#1f2937', fontWeight: '500' },
  cancelButton: {
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#ef4444',
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  cancelButtonText: { color: '#ef4444', fontSize: 15, fontWeight: '600' },
});
