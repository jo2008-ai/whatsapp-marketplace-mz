import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, ScrollView, RefreshControl, StyleSheet, TouchableOpacity,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { lojaApi, encomendaApi } from '../api/endpoints';
import StatCard from '../components/StatCard';
import EncomendaCard from '../components/EncomendaCard';
import LoadingOverlay from '../components/LoadingOverlay';

export default function DashboardScreen({ navigation }) {
  const { user } = useAuth();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const res = await lojaApi.dashboard();
      if (res.data.success) {
        setData(res.data.data);
      }
    } catch (err) {
      console.error('Dashboard error:', err);
    }
    setLoading(false);
    setRefreshing(false);
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  async function handleProximoEstado(id, estado) {
    try {
      await encomendaApi.updateEstado(id, estado);
      fetchData();
    } catch (err) {
      console.error('Erro ao actualizar estado:', err);
    }
  }

  if (loading) return <LoadingOverlay />;

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchData(); }} />}
    >
      <Text style={styles.saudacao}>Olá, {user?.name?.split(' ')[0] || 'Loja'}!</Text>

      <View style={styles.statsRow}>
        <StatCard label="Produtos" value={data?.total_produtos || 0} color="#2563EB" />
        <StatCard label="Hoje" value={data?.encomendas_hoje || 0} color="#10b981" />
      </View>
      <View style={styles.statsRow}>
        <StatCard label="Pendentes" value={data?.encomendas_pendentes || 0} color="#f59e0b" />
        <StatCard label="Receita Mês" value={`${((data?.receita_mes || 0) / 1000).toFixed(0)}k`} color="#8b5cf6" />
      </View>

      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Últimas Encomendas</Text>
        <TouchableOpacity onPress={() => navigation.navigate('Encomendas')}>
          <Text style={styles.seeAll}>Ver todas</Text>
        </TouchableOpacity>
      </View>

      {data?.encomendas_recentes?.length > 0 ? (
        data.encomendas_recentes.map((e) => (
          <EncomendaCard key={e.id} encomenda={e} onProximoEstado={handleProximoEstado} />
        ))
      ) : (
        <Text style={styles.empty}>Sem encomendas recentes.</Text>
      )}

      <View style={{ height: 80 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  saudacao: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#1f2937',
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 8,
  },
  statsRow: {
    flexDirection: 'row',
    paddingHorizontal: 12,
    marginBottom: 8,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    marginTop: 16,
    marginBottom: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#1f2937',
  },
  seeAll: {
    fontSize: 13,
    color: '#2563EB',
    fontWeight: '500',
  },
  empty: {
    textAlign: 'center',
    color: '#9ca3af',
    paddingVertical: 24,
    fontSize: 14,
  },
});
