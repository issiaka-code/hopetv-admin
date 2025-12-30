import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ToastProvider } from './contexts/ToastContext';

// Layouts
import AdminLayout from './layouts/AdminLayout';
import PublicLayout from './layouts/PublicLayout';
import AuthLayout from './layouts/AuthLayout';

// Admin Pages
import Dashboard from './pages/admin/Dashboard';
import Evenements from './pages/admin/Evenements';
import Utilisateurs from './pages/admin/Utilisateurs';
import Tickets from './pages/admin/Tickets';

// Public Pages
import Home from './pages/Home';
import EventsList from './pages/EventsList';
import EventDetail from './pages/EventDetail';
import EventCreate from './pages/EventCreate';
import EventCreatePage from './pages/EventCreatePage';
import EventEdit from './pages/EventEdit';
import EventDashboard from './pages/EventDashboard';
import UserDashboard from './pages/UserDashboard';
import Profile from './pages/Profile';
import MyEvents from './pages/MyEvents';
import MyParticipations from './pages/MyParticipations';
import Friends from './pages/Friends';
import Messages from './pages/Messages';
import Favorites from './pages/Favorites';
import CompetitionDetail from './pages/CompetitionDetail';

// Auth Pages
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';

// Components
import ProtectedRoute from './components/ProtectedRoute';

console.log('🚀 app.jsx chargé');

const container = document.getElementById('app');

if (container) {
    console.log('✅ Container trouvé, initialisation React Router...');
    const root = createRoot(container);
    root.render(
        <React.StrictMode>
            <ToastProvider>
                <AuthProvider>
                    <BrowserRouter>
                    <Routes>
                        {/* Routes Admin */}
                        <Route path="/admin" element={
                            <ProtectedRoute allowedRoles={['admin']}>
                                <AdminLayout />
                            </ProtectedRoute>
                        }>
                            <Route index element={<Navigate to="/admin/dashboard" replace />} />
                            <Route path="dashboard" element={<Dashboard />} />
                            <Route path="evenements" element={<Evenements />} />
                            <Route path="utilisateurs" element={<Utilisateurs />} />
                            <Route path="tickets" element={<Tickets />} />
                        </Route>

                        {/* Routes Publiques */}
                        <Route path="/" element={<PublicLayout />}>
                            <Route index element={<Home />} />
                            <Route path="evenements" element={<EventsList />} />
                            <Route path="evenements/:id" element={<EventDetail />} />
                            <Route path="competitions/:id" element={<CompetitionDetail />} />
                            
                            {/* Routes protégées */}
                            <Route
                                path="evenements/create"
                                element={
                                    <ProtectedRoute>
                                        <EventCreatePage />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="evenements/:id/edit"
                                element={
                                    <ProtectedRoute>
                                        <EventEdit />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="evenements/:id/dashboard"
                                element={
                                    <ProtectedRoute>
                                        <EventDashboard />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="dashboard"
                                element={
                                    <ProtectedRoute allowedRoles={['user']}>
                                        <UserDashboard />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="profile"
                                element={
                                    <ProtectedRoute>
                                        <Profile />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="mes-evenements"
                                element={
                                    <ProtectedRoute>
                                        <MyEvents />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="mes-participations"
                                element={
                                    <ProtectedRoute>
                                        <MyParticipations />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="amis"
                                element={
                                    <ProtectedRoute>
                                        <Friends />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="messages"
                                element={
                                    <ProtectedRoute>
                                        <Messages />
                                    </ProtectedRoute>
                                }
                            />
                            <Route
                                path="favoris"
                                element={
                                    <ProtectedRoute>
                                        <Favorites />
                                    </ProtectedRoute>
                                }
                            />
                        </Route>

                        {/* Routes Authentification */}
                        <Route path="/" element={<AuthLayout />}>
                            <Route path="login" element={<Login />} />
                            <Route path="register" element={<Register />} />
                        </Route>

                        {/* Catch all */}
                        <Route path="*" element={<Navigate to="/" replace />} />
                    </Routes>
                </BrowserRouter>
                </AuthProvider>
            </ToastProvider>
        </React.StrictMode>
    );
    console.log('✅ React Router initialisé avec succès !');
} else {
    console.error('❌ Élément #app non trouvé dans le DOM');
}
