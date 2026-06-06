import React from 'react';
import { View, Text, Image, TouchableOpacity, StyleSheet } from 'react-native';

export default function ProdutoCard({ produto, onEdit, onToggle }) {
  return (
    <View style={styles.card}>
      <Image
        source={{ uri: produto.imagem_url || 'https://via.placeholder.com/80' }}
        style={styles.image}
      />
      <View style={styles.info}>
        <Text style={styles.nome} numberOfLines={1}>{produto.nome}</Text>
        <Text style={styles.preco}>{Number(produto.preco).toLocaleString('pt-MZ')} MZN</Text>
        <Text style={[styles.stock, produto.stock < 3 && styles.stockLow]}>
          Stock: {produto.stock}
        </Text>
      </View>
      <View style={styles.actions}>
        <TouchableOpacity onPress={onEdit} style={styles.btnEdit}>
          <Text style={styles.btnEditText}>Editar</Text>
        </TouchableOpacity>
        <TouchableOpacity
          onPress={onToggle}
          style={[styles.btnToggle, produto.disponivel ? styles.btnOn : styles.btnOff]}
        >
          <Text style={styles.btnToggleText}>
            {produto.disponivel ? 'Activo' : 'Inactivo'}
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    marginHorizontal: 16,
    marginVertical: 4,
    elevation: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    alignItems: 'center',
  },
  image: {
    width: 56,
    height: 56,
    borderRadius: 8,
    backgroundColor: '#f3f4f6',
  },
  info: {
    flex: 1,
    marginLeft: 12,
  },
  nome: {
    fontSize: 15,
    fontWeight: '600',
    color: '#1f2937',
  },
  preco: {
    fontSize: 14,
    color: '#2563EB',
    fontWeight: '500',
    marginTop: 2,
  },
  stock: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 2,
  },
  stockLow: {
    color: '#ef4444',
    fontWeight: '600',
  },
  actions: {
    alignItems: 'flex-end',
    gap: 4,
  },
  btnEdit: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
    backgroundColor: '#eff6ff',
  },
  btnEditText: {
    color: '#2563EB',
    fontSize: 12,
    fontWeight: '500',
  },
  btnToggle: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
  },
  btnOn: {
    backgroundColor: '#ecfdf5',
  },
  btnOff: {
    backgroundColor: '#fef2f2',
  },
  btnToggleText: {
    fontSize: 12,
    fontWeight: '500',
    color: '#374151',
  },
});
