import React from 'react';
import { createRoot } from 'react-dom/client';
import './styles/app.scss';
import { App } from './pages/App';

const rootElement = document.getElementById('app');

if (rootElement) {
  createRoot(rootElement).render(<App />);
}
