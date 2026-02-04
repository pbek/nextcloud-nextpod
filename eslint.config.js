const globals = require("globals");

module.exports = [
  {
    ignores: ["js/**", "node_modules/**", "vendor/**"],
  },
  {
    files: ["src/**/*.js", "*.js"],
    languageOptions: {
      ecmaVersion: 2020,
      sourceType: "module",
      globals: {
        ...globals.browser,
        ...globals.node,
      },
    },
    rules: {
      // Basic rules - minimal for now since full @nextcloud config not compatible with ESLint 9
      "no-unused-vars": "warn",
      "no-undef": "error",
    },
  },
];
