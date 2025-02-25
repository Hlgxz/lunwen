function page (path) {
  return () => import(/* webpackChunkName: '' */ `~/pages/${path}`).then(m => m.default || m)
}

export default [
  { path: '/', name: 'welcome', component: page('welcome.vue') },
  { path: '/student', name: 'student', component: page('student/index.vue') },
  { path: '/login', name: 'login', component: page('auth/login.vue') },
  { path: '/register', name: 'register', component: page('auth/register.vue') },
  { path: '/password/reset', name: 'password.request', component: page('auth/password/email.vue') },
  { path: '/password/reset/:token', name: 'password.reset', component: page('auth/password/reset.vue') },
  { path: '/email/verify/:id', name: 'verification.verify', component: page('auth/verification/verify.vue') },
  { path: '/email/resend', name: 'verification.resend', component: page('auth/verification/resend.vue') },
  { path: '/upload', name: 'upload', component: page('student/upload.vue') },
  { path: '/ThesisManagement', name: 'ThesisManagement', component: page('student/thesisManagement.vue') },
  { path: '/appeals', name: 'appeals', component: page('student/appeals.vue') },
  { path: '/home', name: 'home', component: page('home.vue') },
  {
    path: '/settings',
    component: page('settings/index.vue'),
    children: [
      { path: '', redirect: { name: 'settings.profile' } },
      { path: 'profile', name: 'settings.profile', component: page('settings/profile.vue') },
      { path: 'password', name: 'settings.password', component: page('settings/password.vue') }
    ]
  },
  {
    path: '/operation-logs',
    name: 'operation-logs',
    component: () => import('../pages/OperationLogs.vue'),
    meta: {
      middleware: ['auth'],
      title: '操作日志'
    }
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('../pages/UserList.vue'),
    meta: { middleware: ['auth'] }
  },
  { path: '*', component: page('errors/404.vue') }
]
