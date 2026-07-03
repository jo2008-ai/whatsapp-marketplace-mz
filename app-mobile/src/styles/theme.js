import { StyleSheet } from 'react-native';
import { COLORS } from '../constants/colors';

export const sharedStyles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.bg },
  scrollContent: { padding: 16, paddingBottom: 40 },
  label: { fontSize: 13, fontWeight: '600', color: COLORS.text, marginBottom: 4, marginTop: 8 },
  input: {
    borderWidth: 1, borderColor: COLORS.border, borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 10, fontSize: 14, marginBottom: 8,
  },
  inputMultiline: {
    height: 80, textAlignVertical: 'top',
  },
  row: { flexDirection: 'row' },
  rowBetween: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: COLORS.bgGrayDark,
  },
  catChip: {
    paddingHorizontal: 14, paddingVertical: 6, borderRadius: 20,
    backgroundColor: COLORS.bgGrayDark, marginRight: 8, borderWidth: 1, borderColor: COLORS.border,
  },
  catChipActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  catChipText: { fontSize: 13, color: COLORS.text },
  catChipTextActive: { color: COLORS.white, fontWeight: '600' },
  imageContainer: { position: 'relative', marginBottom: 12 },
  imageBtn: {
    borderWidth: 1, borderColor: COLORS.border, borderRadius: 10,
    height: 160, justifyContent: 'center', alignItems: 'center', overflow: 'hidden',
  },
  imageBtnText: { color: COLORS.textMuted, fontSize: 14 },
  imagePreview: { width: '100%', height: '100%', resizeMode: 'cover' },
  removeBtn: {
    position: 'absolute', top: 8, right: 8,
    backgroundColor: COLORS.danger, borderRadius: 12,
    width: 24, height: 24, justifyContent: 'center', alignItems: 'center',
  },
  removeBtnText: { color: COLORS.white, fontSize: 12, fontWeight: '700' },
  saveBtn: {
    backgroundColor: COLORS.primary, borderRadius: 10, paddingVertical: 14,
    alignItems: 'center', marginTop: 20,
  },
  saveBtnText: { color: COLORS.white, fontWeight: '700', fontSize: 16 },
  deleteBtn: {
    borderWidth: 1, borderColor: COLORS.danger, borderRadius: 10, paddingVertical: 14,
    alignItems: 'center', marginTop: 12,
  },
  deleteBtnText: { color: COLORS.danger, fontWeight: '600', fontSize: 15 },
  emptyText: { textAlign: 'center', color: COLORS.textMuted, marginTop: 40, fontSize: 15 },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  chip: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.indigo,
    borderRadius: 16, paddingHorizontal: 10, paddingVertical: 4, gap: 4,
  },
  chipText: { fontSize: 13, color: COLORS.indigoText },
  chipRemove: { fontSize: 12, color: COLORS.indigoBorder, fontWeight: '700' },
  chipsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 8 },
  inputRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  addBtn: {
    backgroundColor: COLORS.primary, borderRadius: 10,
    width: 36, height: 36, justifyContent: 'center', alignItems: 'center',
  },
  addBtnText: { color: COLORS.white, fontSize: 18, fontWeight: '700' },
  variantesContainer: {
    borderWidth: 1, borderColor: COLORS.border, borderRadius: 10,
    padding: 12, marginTop: 8, marginBottom: 4,
  },
  variantesTitle: { fontSize: 14, fontWeight: '700', color: COLORS.text, marginBottom: 8 },
  tamanhoToggle: {
    borderWidth: 1, borderColor: COLORS.borderLight, borderRadius: 8,
    paddingHorizontal: 14, paddingVertical: 6, backgroundColor: COLORS.bgGray,
  },
  tamanhoToggleActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  tamanhoText: { fontSize: 13, color: COLORS.text },
  tamanhoTextActive: { color: COLORS.white, fontWeight: '600' },
  fab: {
    position: 'absolute', bottom: 20, right: 20,
    backgroundColor: COLORS.primary, borderRadius: 28,
    width: 56, height: 56, justifyContent: 'center', alignItems: 'center',
    elevation: 4, shadowColor: COLORS.black, shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25, shadowRadius: 4,
  },
  fabText: { color: COLORS.white, fontSize: 24, fontWeight: '700' },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 16 },
  headerTitle: { fontSize: 20, fontWeight: '700', color: COLORS.text },
  searchBar: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: COLORS.bgGrayDark,
    borderRadius: 10, paddingHorizontal: 12, marginHorizontal: 16, marginBottom: 12,
    borderWidth: 1, borderColor: COLORS.border,
  },
  searchInput: { flex: 1, paddingVertical: 10, fontSize: 14 },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: COLORS.text, marginBottom: 8 },
});
