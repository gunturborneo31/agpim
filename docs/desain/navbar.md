# Design Specification: Navigation System

Spesifikasi ini mendefinisikan komponen navigasi untuk "Modern Executive Interface", memastikan konsistensi visual dan fungsionalitas di seluruh aplikasi.

## 1. Top App Bar
Bar atas yang memberikan konteks halaman dan akses ke profil admin.

*   **Background**: `bg-surface/80` dengan `backdrop-blur-xl`.
*   **Brand Logo**: Menggunakan font Plus Jakarta Sans, Bold, warna `Primary` (#0062ff).
*   **Leading Element**: Ikon institusi (Outlined) untuk memberikan identitas resmi.
*   **Trailing Element**: Avatar profil dengan border halus dan status online.
*   **Shadow**: `shadow-sm` untuk pemisahan lembut dari konten utama.

## 2. Bottom Navigation (Mobile Primary)
Navigasi utama untuk akses cepat menggunakan ibu jari.

*   **Shape**: `rounded-t-2xl` (16px) dengan latar belakang `Surface Container Lowest` (#ffffff).
*   **Active State**: Ikon dan label berwarna `Primary` (#0062ff). Menggunakan indikator pill di belakang ikon.
*   **Inactive State**: Warna `On-Surface Variant` (#44474e) dengan opasitas 70%.
*   **Labels**: Font size 10-12px, Semi-bold.

## 3. Navigation Drawer (Side Menu)
Menu sekunder untuk navigasi mendalam dan pengaturan akun.

*   **Header Section**: Menampilkan profil lengkap (Nama, Peran, Instansi) dengan latar belakang gradien lembut atau `Surface Container Low`.
*   **Menu Items**: 
    *   `Rounded-full` (Capsule shape) untuk status aktif.
    *   Padding horizontal yang luas (px-6).
    *   Ikon di sebelah kiri label dengan jarak 16px.
*   **Separation**: Menggunakan divider tipis (`border-surface-dim`) untuk memisahkan grup menu seperti 'Utama' dan 'Akun'.

## 4. Interaction States
*   **Hover/Focus**: `bg-primary/5` (biru sangat muda).
*   **Press**: Skala mengecil sedikit (scale-95) untuk memberikan feedback haptic visual.
*   **Transition**: 200ms ease-in-out untuk perubahan warna dan posisi.

## 5. Token Mappings (Tailwind)
*   **Container**: `bg-surface-container-lowest`
*   **Active Icon/Text**: `text-primary`
*   **Inactive Icon/Text**: `text-on-surface-variant`
*   **Shape**: `rounded-2xl` (for floating menus) or `rounded-full` (for items)
