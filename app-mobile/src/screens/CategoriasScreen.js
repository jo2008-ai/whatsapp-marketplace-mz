import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, FlatList, TouchableOpacity,
  TextInput, Alert, Modal, ActivityIndicator, RefreshControl,
} from 'react-native';
import { categoriaApi } from '../api/endpoints';

export default function CategoriasScreen() {
  const [categorias, setCategorias] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [editing, setEditing] = useState(null);
  const [nome, setNome] = useState('');
  const [descricao, setDescricao] = useState('');
  const [icone, setIcone] = useState('');
  const [saving, setSaving] = useState(false);

  const fetchCategorias = useCallback(async () => {
    try {
      const res = await categoriaApi.list();
      setCategorias(res.data.data || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => { fetchCategorias(); }, [fetchCategorias]);

  const onRefresh = () => { setRefreshing(true); fetchCategorias(); };

  const openCreate = () => {
    setEditing(null);
    setNome('');
    setDescricao('');
    setIcone('');
    setModalVisible(true);
  };

  const openEdit = (cat) => {
    setEditing(cat);
    setNome(cat.nome);
    setDescricao(cat.descricao || '');
    setIcone(cat.icone || '');
    setModalVisible(true);
  };

  const handleSave = async () => {
    if (!nome.trim()) {
      Alert.alert('Erro', 'O nome é obrigatório.');
      return;
    }
    setSaving(true);
    try {
      const data = { nome: nome.trim(), descricao: descricao.trim(), icone: icone.trim() };
      if (editing) {
        await categoriaApi.update(editing.id, data);
      } else {
        await categoriaApi.create(data);
      }
      setModalVisible(false);
      fetchCategorias();
    } catch (err) {
      Alert.alert('Erro', err.response?.data?.message || 'Erro ao guardar.');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = (cat) => {
    Alert.alert('Confirmar', `Eliminar a categoria "${cat.nome}"?`, [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: async () => {
          try {
            await categoriaApi.delete(cat.id);
            fetchCategorias();
          } catch (err) {
            Alert.alert('Erro', err.response?.data?.message || 'Não foi possível eliminar.');
          }
        },
      },
    ]);
  };

  const renderItem = ({ item }) => (
    <TouchableOpacity style={styles.card} onPress={() => openEdit(item)}>
      <View style={styles.cardHeader}>
        <Text style={styles.cardIcon}>{item.icone || '📦'}</Text>
        <View style={styles.cardInfo}>
          <Text style={styles.cardNome}>{item.nome}</Text>
          {item.descricao ? <Text style={styles.cardDesc}>{item.descricao}</Text> : null}
        </View>
        <Text style={styles.cardCount}>{item.produtos_count || 0} produtos</Text>
      </View>
    </TouchableOpacity>
  );

  if (loading) {
    return <View style={styles.center}><ActivityIndicator size="large" color="#2563EB" /></View>;
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={categorias}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={<Text style={styles.empty}>Nenhuma categoria.</Text>}
        contentContainerStyle={categorias.length === 0 && styles.emptyContainer}
      />

      <TouchableOpacity style={styles.fab} onPress={openCreate}>
        <Text style={styles.fabText}>+</Text>
      </TouchableOpacity>

      <Modal visible={modalVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>{editing ? 'Editar Categoria' : 'Nova Categoria'}</Text>

            <Text style={styles.label}>Nome *</Text>
            <TextInput style={styles.input} value={nome} onChangeText={setNome} placeholder="Nome da categoria" />

            <Text style={styles.label}>Descrição</Text>
            <TextInput style={styles.input} value={descricao} onChangeText={setDescricao} placeholder="Descrição (opcional)" />

            <Text style={styles.label}>Ícone (emoji)</Text>
            <TextInput style={styles.input} value={icone} onChangeText={setIcone} placeholder="Ex: 🍎" />

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
  cardIcon: { fontSize: 24, marginRight: 12 },
  cardInfo: { flex: 1 },
  cardNome: { fontSize: 15, fontWeight: '600', color: '#1f2937' },
  cardDesc: { fontSize: 12, color: '#9ca3af', marginTop: 2 },
  cardCount: { fontSize: 12, color: '#6b7280' },
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
  modalButtons: { flexDirection: 'row', justifyContent: 'flex-end', gap: 12, marginTop: 24 },
  cancelBtn: { paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8 },
  cancelBtnText: { color: '#6b7280', fontWeight: '600' },
  saveBtn: {
    backgroundColor: '#2563EB', paddingHorizontal: 24, paddingVertical: 10, borderRadius: 8,
  },
  saveBtnText: { color: '#fff', fontWeight: '600' },
});
