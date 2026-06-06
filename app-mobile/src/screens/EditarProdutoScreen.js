import React, { useState, useEffect } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, ScrollView,
  StyleSheet, Alert, Image, Switch,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { produtoApi, categoriaApi, uploadApi } from '../api/endpoints';

const TAMANHOS_PREDEFINIDOS = ['S', 'M', 'L', 'XL'];

export default function EditarProdutoScreen({ route, navigation }) {
  const { produto } = route.params;

  const [nome, setNome] = useState(produto.nome);
  const [descricao, setDescricao] = useState(produto.descricao || '');
  const [preco, setPreco] = useState(String(produto.preco));
  const [stock, setStock] = useState(String(produto.stock));
  const [categoriaId, setCategoriaId] = useState(produto.categoria_id);
  const [vendedorId, setVendedorId] = useState(produto.vendedor_id);
  const [disponivel, setDisponivel] = useState(produto.disponivel);
  const [destaque, setDestaque] = useState(produto.destaque);
  const [imagem, setImagem] = useState(null);
  const [imagem2, setImagem2] = useState(null);
  const [imagemUrl, setImagemUrl] = useState(produto.imagem_url);
  const [imagem2Url, setImagem2Url] = useState(produto.imagem2_url || null);
  const [categorias, setCategorias] = useState([]);
  const [loading, setLoading] = useState(false);

  const [cores, setCores] = useState(produto.cores || []);
  const [novaCor, setNovaCor] = useState('');
  const [tamanhos, setTamanhos] = useState(produto.tamanhos || []);

  useEffect(() => {
    categoriaApi.list().then((res) => {
      if (res.data.success) setCategorias(res.data.data);
    }).catch(() => {});
  }, []);

  async function pickImage(isSecond) {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: 'Images',
      allowsEditing: true,
      aspect: [4, 3],
      quality: 0.8,
    });
    if (!result.canceled) {
      if (isSecond) {
        setImagem2(result.assets[0]);
      } else {
        setImagem(result.assets[0]);
      }
    }
  }

  function removeImage(isSecond) {
    if (isSecond) {
      setImagem2(null);
      setImagem2Url(null);
    } else {
      setImagem(null);
      setImagemUrl(null);
    }
  }

  function adicionarCor() {
    const cor = novaCor.trim();
    if (!cor) return;
    if (cores.includes(cor)) {
      Alert.alert('Aviso', 'Essa cor já foi adicionada.');
      return;
    }
    if (cores.length >= 10) {
      Alert.alert('Aviso', 'Máximo de 10 cores.');
      return;
    }
    setCores([...cores, cor]);
    setNovaCor('');
  }

  function removerCor(index) {
    setCores(cores.filter((_, i) => i !== index));
  }

  function toggleTamanho(t) {
    if (tamanhos.includes(t)) {
      setTamanhos(tamanhos.filter((x) => x !== t));
    } else {
      if (tamanhos.length >= 10) {
        Alert.alert('Aviso', 'Máximo de 10 tamanhos.');
        return;
      }
      setTamanhos([...tamanhos, t]);
    }
  }

  async function uploadImage(imageAsset) {
    const formData = new FormData();
    formData.append('imagem', {
      uri: imageAsset.uri,
      type: 'image/jpeg',
      name: 'produto.jpg',
    });
    const uploadRes = await uploadApi.imagem(formData);
    if (uploadRes.data.success) {
      return uploadRes.data.data.url;
    }
    return null;
  }

  async function handleSave() {
    if (!nome || !preco || !stock || !categoriaId) {
      Alert.alert('Erro', 'Preenche todos os campos obrigatórios.');
      return;
    }
    if (!imagem && !imagemUrl) {
      Alert.alert('Erro', 'A foto da frente é obrigatória.');
      return;
    }

    setLoading(true);
    try {
      let finalImageUrl = imagemUrl;
      let finalImageUrl2 = imagem2Url;

      if (imagem) {
        finalImageUrl = await uploadImage(imagem);
      }

      if (imagem2) {
        finalImageUrl2 = await uploadImage(imagem2);
      }

      const data = {
        nome,
        descricao,
        preco: Number(preco),
        stock: Number(stock),
        categoria_id: categoriaId,
        vendedor_id: vendedorId,
        disponivel,
        destaque,
        imagem_url: finalImageUrl,
        imagem2_url: finalImageUrl2,
        cores: cores.length > 0 ? cores : null,
        tamanhos: tamanhos.length > 0 ? tamanhos : null,
      };

      const res = await produtoApi.update(produto.id, data);
      if (res.data.success) {
        Alert.alert('Sucesso', 'Produto actualizado!', [
          { text: 'OK', onPress: () => navigation.goBack() },
        ]);
      } else {
        Alert.alert('Erro', res.data.message);
      }
    } catch (err) {
      Alert.alert('Erro', err.response?.data?.message || 'Erro ao guardar.');
    }
    setLoading(false);
  }

  async function handleDelete() {
    Alert.alert('Confirmar', 'Eliminar este produto?', [
      { text: 'Cancelar', style: 'cancel' },
      {
        text: 'Eliminar',
        style: 'destructive',
        onPress: async () => {
          try {
            await produtoApi.delete(produto.id);
            navigation.goBack();
          } catch {
            Alert.alert('Erro', 'Não foi possível eliminar.');
          }
        },
      },
    ]);
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={{ padding: 16 }}>
      <Text style={styles.label}>Nome *</Text>
      <TextInput style={styles.input} value={nome} onChangeText={setNome} />

      <Text style={styles.label}>Descrição</Text>
      <TextInput
        style={[styles.input, { height: 80, textAlignVertical: 'top' }]}
        value={descricao} onChangeText={setDescricao} multiline
      />

      <View style={styles.row}>
        <View style={{ flex: 1 }}>
          <Text style={styles.label}>Preço (MZN) *</Text>
          <TextInput style={styles.input} value={preco} onChangeText={setPreco} keyboardType="numeric" />
        </View>
        <View style={{ flex: 1, marginLeft: 8 }}>
          <Text style={styles.label}>Stock *</Text>
          <TextInput style={styles.input} value={stock} onChangeText={setStock} keyboardType="numeric" />
        </View>
      </View>

      <Text style={styles.label}>Categoria *</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 12 }}>
        {categorias.map((cat) => (
          <TouchableOpacity
            key={cat.id}
            style={[styles.catChip, categoriaId === cat.id && styles.catChipActive]}
            onPress={() => setCategoriaId(cat.id)}
          >
            <Text style={[styles.catChipText, categoriaId === cat.id && styles.catChipTextActive]}>
              {cat.icone ? `${cat.icone} ` : ''}{cat.nome}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <Text style={styles.label}>Foto frente *</Text>
      <View style={styles.imageContainer}>
        <TouchableOpacity style={styles.imageBtn} onPress={() => pickImage(false)}>
          {imagem ? (
            <Image source={{ uri: imagem.uri }} style={styles.imagePreview} />
          ) : imagemUrl ? (
            <Image source={{ uri: imagemUrl }} style={styles.imagePreview} />
          ) : (
            <Text style={styles.imageBtnText}>🖼 Escolher da galeria</Text>
          )}
        </TouchableOpacity>
        {(imagem || imagemUrl) && (
          <TouchableOpacity style={styles.removeBtn} onPress={() => removeImage(false)}>
            <Text style={styles.removeBtnText}>✕</Text>
          </TouchableOpacity>
        )}
      </View>

      <Text style={styles.label}>Foto trás (opcional)</Text>
      <View style={styles.imageContainer}>
        <TouchableOpacity style={styles.imageBtn} onPress={() => pickImage(true)}>
          {imagem2 ? (
            <Image source={{ uri: imagem2.uri }} style={styles.imagePreview} />
          ) : imagem2Url ? (
            <Image source={{ uri: imagem2Url }} style={styles.imagePreview} />
          ) : (
            <Text style={styles.imageBtnText}>🖼 Escolher da galeria</Text>
          )}
        </TouchableOpacity>
        {(imagem2 || imagem2Url) && (
          <TouchableOpacity style={styles.removeBtn} onPress={() => removeImage(true)}>
            <Text style={styles.removeBtnText}>✕</Text>
          </TouchableOpacity>
        )}
      </View>

      <View style={styles.switchRow}>
        <Text style={styles.label}>Disponível</Text>
        <Switch value={disponivel} onValueChange={setDisponivel} />
      </View>
      <View style={styles.switchRow}>
        <Text style={styles.label}>Destaque</Text>
        <Switch value={destaque} onValueChange={setDestaque} />
      </View>

      <View style={styles.variantesContainer}>
        <Text style={styles.variantesTitle}>Variantes (opcional)</Text>

        <Text style={styles.label}>Cores disponíveis</Text>
        <View style={styles.chipsRow}>
          {cores.map((cor, i) => (
            <View key={i} style={styles.chip}>
              <Text style={styles.chipText}>{cor}</Text>
              <TouchableOpacity onPress={() => removerCor(i)}>
                <Text style={styles.chipRemove}>✕</Text>
              </TouchableOpacity>
            </View>
          ))}
        </View>
        <View style={styles.inputRow}>
          <TextInput
            style={[styles.input, { flex: 1 }]}
            value={novaCor}
            onChangeText={setNovaCor}
            placeholder="Nova cor..."
            onSubmitEditing={adicionarCor}
          />
          <TouchableOpacity style={styles.addBtn} onPress={adicionarCor}>
            <Text style={styles.addBtnText}>+</Text>
          </TouchableOpacity>
        </View>

        <Text style={[styles.label, { marginTop: 12 }]}>Tamanhos disponíveis</Text>
        <View style={styles.chipsRow}>
          {TAMANHOS_PREDEFINIDOS.map((t) => (
            <TouchableOpacity
              key={t}
              style={[styles.tamanhoToggle, tamanhos.includes(t) && styles.tamanhoToggleActive]}
              onPress={() => toggleTamanho(t)}
            >
              <Text style={[styles.tamanhoText, tamanhos.includes(t) && styles.tamanhoTextActive]}>
                {t}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      </View>

      <TouchableOpacity
        style={[styles.saveBtn, loading && { opacity: 0.6 }]}
        onPress={handleSave} disabled={loading}
      >
        <Text style={styles.saveBtnText}>{loading ? 'Guardando...' : 'Actualizar'}</Text>
      </TouchableOpacity>

      <TouchableOpacity style={styles.deleteBtn} onPress={handleDelete}>
        <Text style={styles.deleteBtnText}>Eliminar Produto</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
  label: { fontSize: 13, fontWeight: '600', color: '#374151', marginBottom: 4, marginTop: 8 },
  input: {
    borderWidth: 1, borderColor: '#e5e7eb', borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 10, fontSize: 14, marginBottom: 8,
  },
  row: { flexDirection: 'row' },
  catChip: {
    paddingHorizontal: 14, paddingVertical: 6, borderRadius: 20,
    backgroundColor: '#f3f4f6', marginRight: 8, borderWidth: 1, borderColor: '#e5e7eb',
  },
  catChipActive: { backgroundColor: '#2563EB', borderColor: '#2563EB' },
  catChipText: { fontSize: 13, color: '#374151' },
  catChipTextActive: { color: '#fff', fontWeight: '600' },
  imageContainer: { position: 'relative', marginBottom: 12 },
  imageBtn: {
    borderWidth: 1, borderColor: '#e5e7eb', borderRadius: 10,
    height: 160, justifyContent: 'center', alignItems: 'center', overflow: 'hidden',
  },
  imageBtnText: { color: '#9ca3af', fontSize: 14 },
  imagePreview: { width: '100%', height: '100%', resizeMode: 'cover' },
  removeBtn: {
    position: 'absolute', top: 8, right: 8,
    backgroundColor: '#ef4444', borderRadius: 12,
    width: 24, height: 24, justifyContent: 'center', alignItems: 'center',
  },
  removeBtnText: { color: '#fff', fontSize: 12, fontWeight: '700' },
  switchRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#f3f4f6',
  },
  saveBtn: {
    backgroundColor: '#2563EB', borderRadius: 10, paddingVertical: 14,
    alignItems: 'center', marginTop: 20,
  },
  saveBtnText: { color: '#fff', fontWeight: '700', fontSize: 16 },
  deleteBtn: {
    borderWidth: 1, borderColor: '#ef4444', borderRadius: 10, paddingVertical: 14,
    alignItems: 'center', marginTop: 12, marginBottom: 40,
  },
  deleteBtnText: { color: '#ef4444', fontWeight: '600', fontSize: 15 },
  variantesContainer: {
    borderWidth: 1, borderColor: '#e5e7eb', borderRadius: 10,
    padding: 12, marginTop: 8, marginBottom: 4,
  },
  variantesTitle: { fontSize: 14, fontWeight: '700', color: '#1f2937', marginBottom: 8 },
  chipsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 8 },
  chip: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#e0e7ff',
    borderRadius: 16, paddingHorizontal: 10, paddingVertical: 4, gap: 4,
  },
  chipText: { fontSize: 13, color: '#3730a3' },
  chipRemove: { fontSize: 12, color: '#6366f1', fontWeight: '700' },
  inputRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  addBtn: {
    backgroundColor: '#2563EB', borderRadius: 10,
    width: 36, height: 36, justifyContent: 'center', alignItems: 'center',
  },
  addBtnText: { color: '#fff', fontSize: 18, fontWeight: '700' },
  tamanhoToggle: {
    borderWidth: 1, borderColor: '#d1d5db', borderRadius: 8,
    paddingHorizontal: 14, paddingVertical: 6, backgroundColor: '#f9fafb',
  },
  tamanhoToggleActive: { backgroundColor: '#2563EB', borderColor: '#2563EB' },
  tamanhoText: { fontSize: 13, color: '#374151' },
  tamanhoTextActive: { color: '#fff', fontWeight: '600' },
});
