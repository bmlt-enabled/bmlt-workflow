module.exports = {
  env: {
    browser: true,
    es2021: true,
  },
  extends: 'airbnb-base',
  overrides: [
  ],
  parserOptions: {
    ecmaVersion: 'latest',
  },
  rules: {
    'prefer-arrow-callback': 'off',
    'func-names': 'off',
    camelcase: 'off',
    'max-len': 'off',
  },
};
