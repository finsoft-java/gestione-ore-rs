const path = require('path');

module.exports = {
    entry: './src/main.ts',
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: ['ts-loader', 'angular2-template-loader'],
                exclude: /node_modules/
            },
            {
                /* raw loader: file content is loaded as string in js */
                test: /\.(html?|svg)$/,
                loader: 'raw-loader'
            },
            {
                /* Angular wants css loaded as strings 
                we need css-loader to parse @import 
                */
                test: /\.css$/i,
                use: ['to-string-loader', 'css-loader'],
            },
        ]
    },
    resolve: {
        extensions: ['.ts', '.js'],
        alias: {
            '@': path.resolve(__dirname, 'src/app/'),
        }
    },
    optimization: {
        splitChunks: {
            chunks: 'all',
        },
        runtimeChunk: true
    }
};