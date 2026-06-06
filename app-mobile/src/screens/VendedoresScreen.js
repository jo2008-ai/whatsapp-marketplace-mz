import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, FlatList, TouchableOpacity,
  TextInput, Alert, Modal, ActivityIndicator, RefreshControl, Switch,
} from 'react-native';
import { vendedorApi } from '../api/endpoints';

export default function VendedoresScreen() {
  const [vendedores, setVendedores] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [editing, setEditing] = useState(null);
  const [nome, setNome] = useState('');
  const [numero, setNumero] = useState('');
  const [descricao, setDescricao] = useState('');
  const [ativo, setAtivo] = useState(true);
  const [saving, setSaving] = useState(false);

  const fetchVendedores = useCallback(async () => {
    try {
      const res = await vendedorApi.list();
      setVendedores(res.data.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { fetchVendedores(); }, [fetchVendedores]);

  const onRefresh = () => { setRefreshing(true); fetchVendedores(); };

  const openCreate = () => {
    setEditing(null);
    setNome('');
    setNumero('');
    setDescricao('');
    setAtivo(true);
    setModalVisible(true);
  };

  const openEdit = (v) => {
    setEditing(v);
    setNome(v.nome);
    setNumero(v.numero_whatsapp);
    setDescricao(v.descricao || '');
    setAtivo(v.ativo);
    setModalVisible(true);
  };

  const handleSave = async () => {
    if (!nome.trim() || !numero.trim()) {
      Alert.alert('Erro', 'Nome e número são obrigatórios.');
      return;
    }
    setSaving(true);
    try {
      const data = { nome: nome.trim(), numero_whatsapp: numero.trim(), descricao: descricao.trim(), ativo };
      if (editing) {
        await vendedorApi.update(editing.id, data);
      } else {
        await vendedorApi.create(data);
      }
      setModalVisible(false);
      fetchVendedores();
    } catch (err) {
      Alert.alert('Erro', err.response?.data?.message || 'Erro ao guardar.');
    } finally {
      setSaving(false);
    }
  };

  const handleToggle = async (v) => {
    try {
      await vendedorApi.toggle(v.id);
      fetchVendedores();
    } catch (err) {
      Alert.alert('Erro', 'Não foi possível alterar estado.');
    }
  };

  const renderItem = ({ item }) => (
    <TouchableOpacity style={styles.card} onPress={() => openEdit(item)}>
      <View style={styles.cardHeader}>
        <View style={styles.cardInfo}>
          <Text style={styles.cardNome}>{item.nome}</Text>
          <Text style={styles.cardNumero}>📱 {item.numero_whatsapp}</Text>
          {item.descricao ? <Text style={styles.cardDesc}>{item.descricao}</Text> : null}
        </View>
        <Switch
          value={item.ativo}
          onValueChange={() => handleToggle(item)}
          trackColor={{ false: '#e5e7eb', true: '#bbf7d0' }}
          thumbColor={item.ativo ? '#22c55e' : '#9ca3af'}
        />
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return <View style={styles.center}><ActivityIndicator size="large" color="#2563EB" /></View>;
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={vendedores}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={<Text style={styles.empty}>Nenhum vendedor.</Text>}
        contentContainerStyle={vendedores.length === 0 && styles.emptyContainer}
      />

      <TouchableOpacity style={styles.fab} onPress={openCreate}>
        <Text style={styles.fabText}>+</Text>
      </TouchableOpacity>

      <Modal visible={modalVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>{editing ? 'Editar Vendedor' : 'Novo Vendedor'}</Text>

            <Text style={styles.label}>Nome *</Text>
            <TextInput style={styles.input} value={nome} onChangeText={setNome} placeholder="Nome do vendedor" />

            <Text style={styles.label}>Número WhatsApp *</Text>
            <TextInput style={styles.input} value={numero} onChangeText={setNumero}
                       placeholder="+258841234567" keyboardType="phone-pad" />

            <Text style={styles.label}>Descrição</Text>
            <TextInput style={styles.input} value={descricao} onChangeText={setDescricao} placeholder="Ex: Vendedor de frutas" />

            <View style={styles.switchRow}>
              <Text style={styles.label}>Activo</Text>
              <Switch
                value={ativo}
                onValueChange={setAtivo}
                trackColor={{ false: '#e5e7eb', true: '#bbf7d0' }}
                thumbColor={ativo ? '#22c55e' : '#9ca3af'}
              />
            </View>

            <View style={styles.modalButtons}>
              <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)}>
                <Text style={styles.cancelBtnText}>Cancelar</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving}>
                <Text style={styles.saveBtnText}>{saving ? 'Guardando...' : 'Guardar'}</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f3f4f6' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: {
    backgroundColor: '#fff', marginHorizontal: 16, marginTop: 12,
    borderRadius: 12, padding: 14,
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center' },
  cardInfo: { flex: 1 },
  cardNome: { fontSize: 15, fontWeight: '600', color: '#1f2937' },
  cardNumero: { fontSize: 13, color: '#6b7280', marginTop: 2 },
  cardDesc: { fontSize: 12, color: '#9ca3af', marginTop: 2 },
  empty: { textAlign: 'center', color: '#9ca3af', marginTop: 40 },
  emptyContainer: { flexGrow: 1 },
  fab: {
    position: 'absolute', bottom: 20, right: 20,
    width: 56, height: 56, borderRadius: 28,
    backgroundColor: '#2563EB', justifyContent: 'center', alignItems: 'center',
    elevation: 4,
  },
  fabText: { color: '#fff', fontSize: 28, lineHeight: 30 },
  modalOverlay: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#fff', borderTopLeftRadius: 20, borderTopRightRadius: 20,
    padding: 24, paddingBottom: 40,
  },
  modalTitle: { fontSize: 18, fontWeight: '700', color: '#1f2937', marginBottom: 20 },
  label: { fontSize: 13, color: '#6b7280', marginBottom: 4, marginTop: 8 },
  input: {
    borderWidth: 1, borderColor: '#e5e7eb', borderRadius: 10,
    paddingHorizontal: 12, paddingVertical: 10, fontSize: 15,
  },
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 8 },
  modalButtons: { flexDirection: 'row', justifyContent: 'flex-end', gap: 12, marginTop: 24 },
  cancelBtn: { paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8 },
  cancelBtnText: { color: '#6b7280', fontWeight: '600' },
  saveBtn: {
    backgroundColor: '#2563EB', paddingHorizontal: 24, paddingVertical: 10, borderRadius: 8,
  },
  saveBtnText: { color: '#fff', fontWeight: '600' },
});
