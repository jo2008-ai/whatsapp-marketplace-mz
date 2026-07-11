import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, ScrollView, RefreshControl, StyleSheet, TouchableOpacity,
  Modal, TextInput, Alert,
} from 'react-native';
import { stockApi } from '../api/endpoints';
import LoadingOverlay from '../components/LoadingOverlay';

export default function StockScreen() {
  const [relatorio, setRelatorio] = useState(null);
  const [movimentos, setMovimentos] = useState([]);
  const [alertas, setAlertas] = useState({ stock_baixo: [], sem_stock: [] });
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [produtoSelecionado, setProdutoSelecionado] = useState(null);
  const [quantidade, setQuantidade] = useState('');
  const [motivo, setMotivo] = useState('reposicao');
  const [submitting, setSubmitting] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const [relRes, histRes, alertRes] = await Promise.all([
        stockApi.relatorio(),
        stockApi.historico({ limite: 20 }),
        stockApi.alertas(),
      ]);
      if (relRes.data.success) setRelatorio(relRes.data.data);
      if (histRes.data.success) setMovimentos(histRes.data.data);
      if (alertRes.data.success) setAlertas(alertRes.data.data);
    } catch (err) {
      console.error('Stock error:', err);
    }
    setLoading(false);
    setRefreshing(false);
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  function abrirModal(produto) {
    setProdutoSelecionado(produto);
    setQuantidade('');
    setMotivo('reposicao');
    setModalVisible(true);
  }

  async function handleRepor() {
    if (!quantidade || Number(quantidade) <= 0) {
      Alert.alert('Erro', 'Indica uma quantidade válida.');
      return;
    }
    setSubmitting(true);
    try {
      await stockApi.entrada(produtoSelecionado.id, {
        quantidade: Number(quantidade),
        motivo,
      });
      setModalVisible(false);
      Alert.alert('Sucesso', 'Stock reposto com sucesso!');
      fetchData();
    } catch (err) {
      Alert.alert('Erro', err.response?.data?.message || 'Erro ao repor stock.');
    }
    setSubmitting(false);
  }

  function getTipoBadge(tipo) {
    const colors = {
      entrada: { bg: '#dcfce7', text: '#166534' },
      saida: { bg: '#fef2f2', text: '#991b1b' },
      ajuste: { bg: '#fef9c3', text: '#854d0e' },
      devolucao: { bg: '#dbeafe', text: '#1e40af' },
    };
    const labels = { entrada: 'Entrada', saida: 'Saída', ajuste: 'Ajuste', devolucao: 'Devolução' };
    const c = colors[tipo] || colors.entrada;
    return (
      <View style={[styles.badge, { backgroundColor: c.bg }]}>
        <Text style={[styles.badgeText, { color: c.text }]}>{labels[tipo] || tipo}</Text>
      </View>
    );
  }

  if (loading) return <LoadingOverlay />;

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchData(); }} />}
    >
      {/* Resumo */}
      <Text style={styles.sectionTitle}>Resumo</Text>
      <View style={styles.statsRow}>
        <View style={[styles.statCard, { borderLeftColor: '#2563EB' }]}>
          <Text style={styles.statLabel}>Total Produtos</Text>
          <Text style={[styles.statValue, { color: '#2563EB' }]}>{relatorio?.total_produtos || 0}</Text>
        </View>
        <View style={[styles.statCard, { borderLeftColor: '#f59e0b' }]}>
          <Text style={styles.statLabel}>Stock Baixo</Text>
          <Text style={[styles.statValue, { color: '#f59e0b' }]}>{relatorio?.stock_baixo || 0}</Text>
        </View>
      </View>
      <View style={styles.statsRow}>
        <View style={[styles.statCard, { borderLeftColor: '#ef4444' }]}>
          <Text style={styles.statLabel}>Sem Stock</Text>
          <Text style={[styles.statValue, { color: '#ef4444' }]}>{relatorio?.sem_stock || 0}</Text>
        </View>
        <View style={[styles.statCard, { borderLeftColor: '#8b5cf6' }]}>
          <Text style={styles.statLabel}>Valor Inventário</Text>
          <Text style={[styles.statValue, { color: '#8b5cf6' }]}>
            {((relatorio?.valor_inventario || 0) / 1000).toFixed(0)}k MZN
          </Text>
        </View>
      </View>

      {/* Alertas */}
      {(alertas.stock_baixo?.length > 0 || alertas.sem_stock?.length > 0) && (
        <>
          <Text style={[styles.sectionTitle, { color: '#ef4444' }]}>⚠️ Alertas</Text>
          {alertas.sem_stock?.map((p) => (
            <TouchableOpacity key={p.id} style={styles.alertCard} onPress={() => abrirModal(p)}>
              <View style={styles.alertInfo}>
                <Text style={styles.alertNome}>{p.nome}</Text>
                <Text style={[styles.alertStock, { color: '#ef4444' }]}>Sem stock</Text>
              </View>
              <Text style={styles.reporBtn}>Repor</Text>
            </TouchableOpacity>
          ))}
          {alertas.stock_baixo?.map((p) => (
            <TouchableOpacity key={p.id} style={styles.alertCard} onPress={() => abrirModal(p)}>
              <View style={styles.alertInfo}>
                <Text style={styles.alertNome}>{p.nome}</Text>
                <Text style={[styles.alertStock, { color: '#f59e0b' }]}>Stock: {p.stock} {p.unidade}</Text>
              </View>
              <Text style={styles.reporBtn}>Repor</Text>
            </TouchableOpacity>
          ))}
        </>
      )}

      {/* Histórico */}
      <Text style={styles.sectionTitle}>📋 Histórico de Movimentos</Text>
      {movimentos.length === 0 ? (
        <Text style={styles.empty}>Sem movimentos registados.</Text>
      ) : (
        movimentos.map((m) => (
          <View key={m.id} style={styles.movimentoCard}>
            <View style={styles.movimentoHeader}>
              {getTipoBadge(m.tipo)}
              <Text style={styles.movimentoData}>{m.created_at}</Text>
            </View>
            <Text style={styles.movimentoProduto}>{m.produto?.nome || '-'}</Text>
            <View style={styles.movimentoDetails}>
              <Text style={styles.movimentoQtd}>
                Qtd: <Text style={{ fontWeight: '700' }}>{m.quantidade}</Text>
              </Text>
              <Text style={styles.movimentoStock}>
                {m.stock_anterior} → {m.stock_actual}
              </Text>
            </View>
            {m.motivo ? <Text style={styles.movimentoMotivo}>{m.motivo}</Text> : null}
          </View>
        ))
      )}

      <View style={{ height: 40 }} />

      {/* Modal Repor Stock */}
      <Modal visible={modalVisible} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Repor Stock</Text>
            <Text style={styles.modalProduto}>{produtoSelecionado?.nome}</Text>
            <Text style={styles.modalStock}>Stock actual: {produtoSelecionado?.stock} {produtoSelecionado?.unidade}</Text>

            <Text style={styles.inputLabel}>Quantidade a adicionar</Text>
            <TextInput
              style={styles.input}
              value={quantidade}
              onChangeText={setQuantidade}
              keyboardType="numeric"
              placeholder="0"
            />

            <Text style={styles.inputLabel}>Motivo</Text>
            <View style={styles.motivoRow}>
              {['reposicao', 'contagem_fisica', 'devolucao'].map((m) => (
                <TouchableOpacity
                  key={m}
                  style={[styles.motivoChip, motivo === m && styles.motivoChipActive]}
                  onPress={() => setMotivo(m)}
                >
                  <Text style={[styles.motivoChipText, motivo === m && styles.motivoChipTextActive]}>
                    {m === 'reposicao' ? 'Reposição' : m === 'contagem_fisica' ? 'Contagem' : 'Devolução'}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)}>
                <Text style={styles.cancelBtnText}>Cancelar</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.confirmBtn, submitting && { opacity: 0.6 }]}
                onPress={handleRepor}
                disabled={submitting}
              >
                <Text style={styles.confirmBtnText}>{submitting ? 'A guardar...' : 'Confirmar'}</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f9fafb', padding: 16 },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: '#1f2937', marginTop: 16, marginBottom: 8 },
  statsRow: { flexDirection: 'row', gap: 8, marginBottom: 8 },
  statCard: { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 12, borderLeftWidth: 3, elevation: 1 },
  statLabel: { fontSize: 12, color: '#6b7280' },
  statValue: { fontSize: 22, fontWeight: '700', marginTop: 2 },
  alertCard: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 10, padding: 12, marginBottom: 6, borderLeftWidth: 3, borderLeftColor: '#ef4444' },
  alertInfo: { flex: 1 },
  alertNome: { fontSize: 14, fontWeight: '600', color: '#1f2937' },
  alertStock: { fontSize: 12, marginTop: 2 },
  reporBtn: { color: '#2563EB', fontWeight: '600', fontSize: 13 },
  empty: { textAlign: 'center', color: '#9ca3af', paddingVertical: 24, fontSize: 14 },
  movimentoCard: { backgroundColor: '#fff', borderRadius: 10, padding: 12, marginBottom: 6, elevation: 1 },
  movimentoHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  badge: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 12 },
  badgeText: { fontSize: 11, fontWeight: '600' },
  movimentoData: { fontSize: 11, color: '#9ca3af' },
  movimentoProduto: { fontSize: 14, fontWeight: '600', color: '#1f2937' },
  movimentoDetails: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 4 },
  movimentoQtd: { fontSize: 13, color: '#6b7280' },
  movimentoStock: { fontSize: 13, color: '#6b7280' },
  movimentoMotivo: { fontSize: 12, color: '#9ca3af', marginTop: 2 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', padding: 20 },
  modalContent: { backgroundColor: '#fff', borderRadius: 16, padding: 20 },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937', marginBottom: 8 },
  modalProduto: { fontSize: 15, fontWeight: '600', color: '#1f2937' },
  modalStock: { fontSize: 13, color: '#6b7280', marginBottom: 16 },
  inputLabel: { fontSize: 13, fontWeight: '600', color: '#374151', marginBottom: 4 },
  input: { borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8, padding: 10, fontSize: 15, marginBottom: 12 },
  motivoRow: { flexDirection: 'row', gap: 8, marginBottom: 16 },
  motivoChip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16, backgroundColor: '#f3f4f6' },
  motivoChipActive: { backgroundColor: '#2563EB' },
  motivoChipText: { fontSize: 12, color: '#6b7280' },
  motivoChipTextActive: { color: '#fff', fontWeight: '600' },
  modalActions: { flexDirection: 'row', gap: 8, justifyContent: 'flex-end' },
  cancelBtn: { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 8, backgroundColor: '#f3f4f6' },
  cancelBtnText: { color: '#6b7280', fontWeight: '500' },
  confirmBtn: { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 8, backgroundColor: '#2563EB' },
  confirmBtnText: { color: '#fff', fontWeight: '600' },
});
