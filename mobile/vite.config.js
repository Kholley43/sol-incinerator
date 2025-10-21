export default {
  build: {
    outDir: 'dist',
    rollupOptions: {
      output: { manualChunks: undefined }   // single bundle
    }
  }
};
