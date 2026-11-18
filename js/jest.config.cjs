module.exports = {
  testEnvironment: 'node',
  extensionsToTreatAsEsm: ['.ts'],
  transform: {
    '^.+\\.tsx?$': ['babel-jest', require('flarum-webpack-config/babel.config.cjs')],
  },
};
