/** Configuration Tailwind : analyse les vues et scripts pour ne garder que les classes utilisees. */
module.exports = {
  content: [
    '../views/**/*.php',
    '../public/**/*.{php,html,js}',
    '../src/**/*.php',
    './safelist.txt',
  ],
  theme: { extend: {} },
  plugins: [],
};
