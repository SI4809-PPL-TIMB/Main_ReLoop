// axios tidak dipakai (semua request memakai fetch), jadi tidak dimuat di setiap halaman (~35 KB).
// Bila nanti dibutuhkan, impor di modul yang memakainya:
//   import axios from 'axios';
//   axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
