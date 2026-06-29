window.TINHTIENCAU_FIREBASE = {
  // Đổi thành true sau khi đã điền đủ config Firebase bên dưới.
  enabled: true,

  // Path gốc trong Realtime Database. Có thể đổi nếu muốn tách nhiều app.
  databasePath: 'tinhtiencau',

  // Chỉ bật khi chạy emulator ở localhost.
  useEmulator: false,
  emulatorHost: '127.0.0.1',
  databaseEmulatorPort: 9000,
  authEmulatorUrl: 'http://127.0.0.1:9099',

  // Lấy trong Firebase console > Project settings > Your apps
  app: {
    apiKey: 'AIzaSyBkM0DbgYXgdPjYrz7eUgHwp68kNsEwfGo',
    authDomain: 'tinhtiencau.firebaseapp.com',
    databaseURL: 'https://tinhtiencau-default-rtdb.asia-southeast1.firebasedatabase.app',
    projectId: 'tinhtiencau',
    appId: '1:62786656002:web:85dd4c1444f6491b46d41b'
  }
};
