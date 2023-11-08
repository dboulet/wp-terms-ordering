import globals from "globals";
import path from "path";
import { fileURLToPath } from "url";
import js from "@eslint/js";
import { FlatCompat } from "@eslint/eslintrc";

const compat = new FlatCompat({
	baseDirectory: path.dirname(fileURLToPath(import.meta.url))
});

export default [
	js.configs.recommended,
	...compat.extends(
		"plugin:@wordpress/eslint-plugin/custom",
		"plugin:@wordpress/eslint-plugin/es5",
		"plugin:@wordpress/eslint-plugin/i18n",
		"plugin:@wordpress/eslint-plugin/jsdoc"
	),
	{
		files: ["javascript/**/*.js"],
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.jquery
			},
			sourceType: "script"
		}
	},
	{
		files: ["*.config.js"],
		languageOptions: {
			ecmaVersion: "latest",
			sourceType: "module"
		},
		rules: {
			"array-bracket-spacing": [ "error", "always", { singleValue: false } ],
			"comma-dangle": [ "error", "never" ],
			quotes: [ "error", "double", { avoidEscape: true } ],
			"space-in-parens": [ "error", "never" ]
		}
	},
	{
		ignores: ["**/*.min.js"]
	}
];
