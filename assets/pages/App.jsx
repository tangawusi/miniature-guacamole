import React, { useEffect, useMemo, useState } from 'react';

const emptyNote = { title: '', content: '', category: '', status: 'new' };

async function jsonFetch(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
    ...options,
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.message || 'Request failed.');
  }

  return data;
}

export function App() {
  const [authMode, setAuthMode] = useState('register');
  const [auth, setAuth] = useState({ loading: true, authenticated: false, user: null });
  const [authForm, setAuthForm] = useState({ email: '', password: '' });
  const [message, setMessage] = useState('');
  const [noteForm, setNoteForm] = useState(emptyNote);
  const [filters, setFilters] = useState({ q: '', status: '', category: '' });
  const [meta, setMeta] = useState({ statuses: ['new', 'todo', 'done'], categories: [] });
  const [notes, setNotes] = useState([]);

  const canUseNotes = Boolean(auth.authenticated && auth.user?.isVerified);

  useEffect(() => {
    loadSession();
  }, []);

  useEffect(() => {
    if (canUseNotes) {
      loadNotes();
    } else {
      setNotes([]);
    }
  }, [auth.authenticated, auth.user?.isVerified, filters.q, filters.status, filters.category]);

  async function loadSession() {
    try {
      const data = await jsonFetch('/api/auth/status');
      setAuth({ loading: false, authenticated: data.authenticated, user: data.user || null });
    } catch {
      setAuth({ loading: false, authenticated: false, user: null });
    }
  }

  async function loadNotes() {
    const params = new URLSearchParams();
    if (filters.q) params.set('q', filters.q);
    if (filters.status) params.set('status', filters.status);
    if (filters.category) params.set('category', filters.category);

    const data = await jsonFetch(`/api/notes?${params.toString()}`);
    setNotes(data.notes || []);
    setMeta(data.filters || meta);
  }

  async function handleRegister(event) {
    event.preventDefault();
    setMessage('');

    try {
      const data = await jsonFetch('/api/auth/register', {
        method: 'POST',
        body: JSON.stringify(authForm),
      });
      setMessage(`${data.message} File: ${data.emailFile}`);
      setAuthMode('login');
    } catch (error) {
      setMessage(error.message);
    }
  }

  async function handleLogin(event) {
    event.preventDefault();
    setMessage('');

    try {
      await jsonFetch('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify(authForm),
      });
      await loadSession();
      setMessage('Logged in successfully.');
      setAuthForm({ email: '', password: '' });
    } catch (error) {
      setMessage(error.message);
    }
  }

  async function handleLogout() {
    setMessage('');
    await fetch('/api/auth/logout', {
      method: 'POST',
      credentials: 'same-origin',
    });
    setAuth({ loading: false, authenticated: false, user: null });
    setNotes([]);
    setMessage('Logged out successfully.');
  }

  async function handleCreateNote(event) {
    event.preventDefault();
    setMessage('');

    try {
      await jsonFetch('/api/notes', {
        method: 'POST',
        body: JSON.stringify(noteForm),
      });
      setNoteForm(emptyNote);
      setMessage('Note created successfully.');
      await loadNotes();
    } catch (error) {
      setMessage(error.message);
    }
  }

  const categoryOptions = useMemo(() => meta.categories || [], [meta.categories]);

  return (
    <main className="shell">
      <section className="hero card">
        <div>
          <h1>AJE Notes Challenge</h1>
          <p>Symfony backend. React SPA. Register, verify, log in, create notes, and filter them.</p>
        </div>
        {auth.authenticated ? (
          <div className="session-box">
            <span>{auth.user?.email}</span>
            <span className={auth.user?.isVerified ? 'pill success' : 'pill warning'}>
              {auth.user?.isVerified ? 'Verified' : 'Unverified'}
            </span>
            <button type="button" onClick={handleLogout}>Logout</button>
          </div>
        ) : null}
      </section>

      {message ? <div className="notice">{message}</div> : null}

      <section className="grid two-up">
        <article className="card">
          <div className="section-head">
            <h2>{authMode === 'register' ? 'Register' : 'Login'}</h2>
            <div className="switcher">
              <button type="button" className={authMode === 'register' ? 'active' : ''} onClick={() => setAuthMode('register')}>Register</button>
              <button type="button" className={authMode === 'login' ? 'active' : ''} onClick={() => setAuthMode('login')}>Login</button>
            </div>
          </div>
          <form onSubmit={authMode === 'register' ? handleRegister : handleLogin} className="stack">
            <label>
              Email
              <input
                type="email"
                value={authForm.email}
                onChange={(event) => setAuthForm({ ...authForm, email: event.target.value })}
                required
              />
            </label>
            <label>
              Password
              <input
                type="password"
                value={authForm.password}
                onChange={(event) => setAuthForm({ ...authForm, password: event.target.value })}
                minLength={8}
                required
              />
            </label>
            <button type="submit">{authMode === 'register' ? 'Create account' : 'Login'}</button>
          </form>
          <p className="muted">Verification emails are persisted as files in <code>var/emails</code>.</p>
        </article>

        <article className="card">
          <div className="section-head">
            <h2>Create note</h2>
            {!canUseNotes ? <span className="pill warning">Verify account first</span> : null}
          </div>
          <form onSubmit={handleCreateNote} className="stack">
            <label>
              Title
              <input value={noteForm.title} onChange={(event) => setNoteForm({ ...noteForm, title: event.target.value })} required disabled={!canUseNotes} />
            </label>
            <label>
              Content
              <textarea value={noteForm.content} onChange={(event) => setNoteForm({ ...noteForm, content: event.target.value })} rows="5" required disabled={!canUseNotes} />
            </label>
            <label>
              Category
              <input value={noteForm.category} onChange={(event) => setNoteForm({ ...noteForm, category: event.target.value })} required disabled={!canUseNotes} />
            </label>
            <label>
              Status
              <select value={noteForm.status} onChange={(event) => setNoteForm({ ...noteForm, status: event.target.value })} disabled={!canUseNotes}>
                {meta.statuses.map((status) => <option key={status} value={status}>{status}</option>)}
              </select>
            </label>
            <button type="submit" disabled={!canUseNotes}>Save note</button>
          </form>
        </article>
      </section>

      <section className="card">
        <div className="section-head">
          <h2>Notes</h2>
          <span className="pill">{notes.length} result(s)</span>
        </div>
        <div className="filters">
          <input
            placeholder="Search title or content"
            value={filters.q}
            onChange={(event) => setFilters({ ...filters, q: event.target.value })}
            disabled={!canUseNotes}
          />
          <select value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })} disabled={!canUseNotes}>
            <option value="">All statuses</option>
            {meta.statuses.map((status) => <option key={status} value={status}>{status}</option>)}
          </select>
          <select value={filters.category} onChange={(event) => setFilters({ ...filters, category: event.target.value })} disabled={!canUseNotes}>
            <option value="">All categories</option>
            {categoryOptions.map((category) => <option key={category} value={category}>{category}</option>)}
          </select>
        </div>

        {auth.loading ? <p className="muted">Loading session...</p> : null}
        {!canUseNotes ? <p className="muted">Once you verify and log in, your notes will show up here.</p> : null}

        <div className="notes-grid">
          {notes.map((note) => (
            <article key={note.id} className="note-card">
              <div className="note-top">
                <h3>{note.title}</h3>
                <span className={`pill status-${note.status}`}>{note.status}</span>
              </div>
              <p>{note.content}</p>
              <div className="note-meta">
                <span>{note.category}</span>
                <span>{new Date(note.createdAt).toLocaleString()}</span>
              </div>
            </article>
          ))}
        </div>
      </section>
    </main>
  );
}
