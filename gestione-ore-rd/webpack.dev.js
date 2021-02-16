const webpack = require('webpack');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const merge = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = merge(common, {
    mode: 'development',
    plugins: [
        new HtmlWebpackPlugin({
            template: './src/index.html',
            filename: 'index.html',
            inject: 'body'
        }),
        new webpack.DefinePlugin({
            // global app config object
            config: JSON.stringify({
                //apiUrl: 'http://192.168.99.100/ws',                   //luca docker
                apiUrl: 'http://localhost/ws',                        //luca
                //apiUrl: 'http://127.0.0.1/questionari/ws',              // ale
                //websocketUrl: 'ws://192.168.99.100:9000'              //luca docker
                websocketUrl: 'ws://localhost:9000'                  //luca
                //websocketUrl: 'ws://127.0.0.1:9000'                    //ale
            })
        })
    ],
    devtool: 'inline-source-map',
    devServer: {
        historyApiFallback: true
    }
});