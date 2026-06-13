import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, FlatList, TouchableOpacity,
  StyleSheet, RefreshControl,
} from 'react-native';
import { encomendaApi } from '../api/endpoints';
import EncomendaCard from '../components/EncomendaCard';
import LoadingOverlay from '../components/LoadingOverlay';

const FILTROS = [
  { key: null, label: 'Todas' },
  { key: 'pendente', label: 'Pendentes' },
  { key: 'confirmada', label: 'Confirmadas' },
  { key: 'entregue', label: 'Entregues' },
];

export default function EncomendasScreen({ navigation }) {
  const [encomendas, setEncomendas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filtro, setFiltro] = useState(null);

  const fetchData = useCallback(async () => {
    try {
      const params = {};
      if (filtro) params.estado = filtro;
      const res = await encomendaApi.list(params);
      if (res.data.success) {
        setEncomendas(res.data.data.data || []);
      }
    } catch (err) {
      console.error('Erro encomendas:', err);
    }
    setLoading(false);
    setRefreshing(false);
  }, [filtro]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  async function handleProximoEstado(id, estado) {
    try {
      await encomendaApi.updateEstado(id, estado);
      fetchData();
    } catch (err) {
      console.error('Erro:', err);
    }
  }

  function handleVerDetalhe(encomenda) {
    navigation.navigate('EncomendaDetalhe', { encomenda });
  }

  if (loading) return <LoadingOverlay />;

  return (
    <View style={styles.container}>
      <View style={styles.filtros}>
        {FILTROS.map((f) => (
          <TouchableOpacity
            key={f.label}
            style={[styles.filtroChip, filtro === f.key && styles.filtroChipActive]}
            onPress={() => setFiltro(f.key)}
          >
            <Text style={[styles.filtroText, filtro === f.key && styles.filtroTextActive]}>
              {f.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <FlatList
        data={encomendas}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <TouchableOpacity onPress={() => handleVerDetalhe(item)}>
            <EncomendaCard encomenda={item} onProximoEstado={handleProximoEstado} />
          </TouchableOpacity>
        )}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchData(); }} />
        }
        ListEmptyComponent={<Text style={styles.empty}>Nenhuma encomenda.</Text>}
        contentContainerStyle={{ paddingVertical: 8 }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  filtros: {
    flexDirection: 'row',
    paddingHorizontal: 12,
    paddingVertical: 10,
    gap: 6,
  },
  filtroChip: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  filtroChipActive: {
    backgroundColor: '#2563EB',
    borderColor: '#2563EB',
  },
  filtroText: {
    fontSize: 13,
    color: '#374151',
  },
  filtroTextActive: {
    color: '#fff',
    fontWeight: '600',
  },
  empty: {
    textAlign: 'center',
    color: '#9ca3af',
    paddingVertical: 32,
  },
});
