import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, FlatList, TextInput, TouchableOpacity,
  StyleSheet, RefreshControl,
} from 'react-native';
import { produtoApi, categoriaApi } from '../api/endpoints';
import ProdutoCard from '../components/ProdutoCard';
import LoadingOverlay from '../components/LoadingOverlay';

export default function ProdutosScreen({ navigation }) {
  const [produtos, setProdutos] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [pesquisa, setPesquisa] = useState('');
  const [categoriaFiltro, setCategoriaFiltro] = useState(null);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  const fetchProdutos = useCallback(async (pageNum = 1, replace = true) => {
    try {
      const params = { page: pageNum };
      if (pesquisa) params.pesquisa = pesquisa;
      if (categoriaFiltro) params.categoria_id = categoriaFiltro;

      const res = await produtoApi.list(params);
      if (res.data.success) {
        const newData = res.data.data.data || [];
        setProdutos((prev) => (replace ? newData : [...prev, ...newData]));
        setHasMore(res.data.data.current_page < res.data.data.last_page);
      }
    } catch (err) {
      console.error('Erro produtos:', err);
    }
    setLoading(false);
    setRefreshing(false);
  }, [pesquisa, categoriaFiltro]);

  useEffect(() => {
    fetchProdutos(1, true);
    setPage(1);
  }, [fetchProdutos]);

  useEffect(() => {
    categoriaApi.list().then((res) => {
      if (res.data.success) setCategorias(res.data.data);
    }).catch(() => {});
  }, []);

  async function handleToggle(id) {
    try {
      await produtoApi.toggle(id);
      fetchProdutos(1, true);
    } catch (err) {
      console.error('Erro toggle:', err);
    }
  }

  function handleLoadMore() {
    if (hasMore && !loading) {
      const nextPage = page + 1;
      setPage(nextPage);
      fetchProdutos(nextPage, false);
    }
  }

  if (loading) return <LoadingOverlay />;

  return (
    <View style={styles.container}>
      {/* Pesquisa */}
      <View style={styles.searchRow}>
        <TextInput
          style={styles.searchInput}
          placeholder="Pesquisar produto..."
          value={pesquisa}
          onChangeText={setPesquisa}
          onSubmitEditing={() => fetchProdutos(1, true)}
          returnKeyType="search"
        />
      </View>

      {/* Filtro categorias */}
      <FlatList
        horizontal
        showsHorizontalScrollIndicator={false}
        data={[{ id: null, nome: 'Todos' }, ...categorias]}
        keyExtractor={(item) => String(item.id || 'all')}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[styles.catChip, categoriaFiltro === item.id && styles.catChipActive]}
            onPress={() => setCategoriaFiltro(item.id)}
          >
            <Text style={[styles.catChipText, categoriaFiltro === item.id && styles.catChipTextActive]}>
              {item.icone ? `${item.icone} ` : ''}{item.nome}
            </Text>
          </TouchableOpacity>
        )}
        style={styles.catList}
        contentContainerStyle={{ paddingHorizontal: 12 }}
      />

      {/* Lista */}
      <FlatList
        data={produtos}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <ProdutoCard
            produto={item}
            onEdit={() => navigation.navigate('EditarProduto', { produto: item })}
            onToggle={() => handleToggle(item.id)}
          />
        )}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); fetchProdutos(1, true); }} />}
        onEndReached={handleLoadMore}
        onEndReachedThreshold={0.3}
        ListEmptyComponent={<Text style={styles.empty}>Nenhum produto encontrado.</Text>}
        contentContainerStyle={{ paddingVertical: 8 }}
      />

      {/* FAB */}
      <TouchableOpacity
        style={styles.fab}
        onPress={() => navigation.navigate('NovoProduto')}
      >
        <Text style={styles.fabText}>+</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f9fafb',
  },
  searchRow: {
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 4,
  },
  searchInput: {
    backgroundColor: '#fff',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 14,
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  catList: {
    maxHeight: 44,
    marginVertical: 8,
  },
  catChip: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#e5e7eb',
    marginHorizontal: 4,
  },
  catChipActive: {
    backgroundColor: '#2563EB',
    borderColor: '#2563EB',
  },
  catChipText: {
    fontSize: 13,
    color: '#374151',
  },
  catChipTextActive: {
    color: '#fff',
    fontWeight: '600',
  },
  empty: {
    textAlign: 'center',
    color: '#9ca3af',
    paddingVertical: 32,
  },
  fab: {
    position: 'absolute',
    right: 20,
    bottom: 20,
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#2563EB',
    justifyContent: 'center',
    alignItems: 'center',
    elevation: 6,
    shadowColor: '#2563EB',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  fabText: {
    color: '#fff',
    fontSize: 28,
    fontWeight: '300',
    marginTop: -2,
  },
});
