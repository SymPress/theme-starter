import '../css/app.css';
import { setupNavigation } from './navigation.js';

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => setupNavigation(), { once: true });
} else {
  setupNavigation();
}
