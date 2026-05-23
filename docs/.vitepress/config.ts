import { defineConfig } from 'vitepress'

function guideSidebar() {
  return [
    {
      text: 'Guide',
      items: [
        { text: 'Overview', link: '/guide/' },
        { text: 'Installation', link: '/guide/installation' },
      ],
    },
    {
      text: 'Maintenance',
      items: [
        { text: 'Maintenance Mode', link: '/maintenance/' },
        { text: 'Uptime', link: '/maintenance/uptime' },
      ],
    },
    {
      text: 'Middleware',
      items: [
        { text: 'security.txt', link: '/middleware/security-txt' },
      ],
    },
    {
      text: 'Healthcheck',
      items: [
        { text: 'Overview', link: '/healthcheck/' },
      ],
    },
    {
      text: 'Panels',
      items: [
        { text: 'L10n DebugKit Panel', link: '/panel/' },
      ],
    },
    {
      text: 'Console',
      items: [
        { text: 'Commands', link: '/console/' },
        { text: 'Bake Templates', link: '/console/bake' },
      ],
    },
    {
      text: 'Component',
      items: [
        { text: 'Setup Component', link: '/component/' },
      ],
    },
    {
      text: 'Controller',
      items: [
        { text: 'Web Backend', link: '/controller/' },
      ],
    },
  ]
}

export default defineConfig({
  title: 'cakephp-setup',
  description: 'Development and maintenance tooling for CakePHP — maintenance mode, healthcheck stack, debug panels, console tools, and enhanced bake templates.',
  base: '/cakephp-setup/',
  lastUpdated: true,
  cleanUrls: true,
  sitemap: {
    hostname: 'https://dereuromark.github.io/cakephp-setup/',
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-setup/favicon.svg', type: 'image/svg+xml' }],
  ],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
      { text: 'Maintenance', link: '/maintenance/', activeMatch: '/maintenance/' },
      { text: 'Healthcheck', link: '/healthcheck/', activeMatch: '/healthcheck/' },
      { text: 'Console', link: '/console/', activeMatch: '/console/' },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/dereuromark/cakephp-setup' },
          { text: 'Packagist', link: 'https://packagist.org/packages/dereuromark/cakephp-setup' },
          { text: 'Issues', link: 'https://github.com/dereuromark/cakephp-setup/issues' },
        ],
      },
    ],
    sidebar: {
      '/guide/': guideSidebar(),
      '/maintenance/': guideSidebar(),
      '/middleware/': guideSidebar(),
      '/healthcheck/': guideSidebar(),
      '/panel/': guideSidebar(),
      '/console/': guideSidebar(),
      '/component/': guideSidebar(),
      '/controller/': guideSidebar(),
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/dereuromark/cakephp-setup' },
    ],
    search: {
      provider: 'local',
    },
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-setup/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Mark Scherer',
    },
  },
})
