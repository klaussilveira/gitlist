const utils = require('corifeus-utils');
const mz = require('mz');

module.exports = function (grunt) {

    const themeDir = './themes/default/less/theme';

    const filesLess = {
        'themes/default/css/bootstrap-default.css': 'themes/default/less/style.less',
        'themes/default/css/fontawesome.css': 'themes/default/less/fontawesome.less',
    }


    grunt.registerTask('build', async function() {
        const done = this.async();

        const root = './node_modules/bootswatch';
        const watches = await mz.fs.readdir(root);
        const themes = [];
        const excluded = ['fonts'];
        const themeCss = {
            'bootstrap-default': '/themes/default/css/bootstrap-default.css',
        }

        await watches.forEachAsync(async(path) => {
            const stat = await mz.fs.stat(`${root}/${path}`);
            if (stat.isDirectory() && !excluded.includes(path)) {
                themes.push(path);
                themeCss[`bootstrap-${path}`] = `/themes/default/css/bootstrap-${path}.css`;
           }
        })
        await utils.fs.ensureDir(themeDir);


        await themes.forEachAsync(async (theme) => {
            const less = `${themeDir}/${theme}.less`;
            await mz.fs.writeFile(less, `
@import "../../../../node_modules/bootstrap/less/bootstrap";
@import "../../../../node_modules/bootswatch/${theme}/variables";
@import "../../../../node_modules/bootswatch/${theme}/bootswatch";
@import "../default";
`)
            filesLess[`themes/default/css/bootstrap-${theme}.css`] = less;
        })
        await mz.fs.writeFile(`./themes/default/js/themes.js`, `
var themes = ${JSON.stringify(themeCss, null, 4)}
`);
        grunt.log.write(themes);
        done();
    })

    require('time-grunt')(grunt);
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean')
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-wiredep');

    grunt.initConfig({
        clean: {
          themes: [
              themeDir
          ],
          fonts: [
              'themes/default/fonts'
          ]
        },
        copy: {
            bootstrap: {
                expand: true,
                cwd: './node_modules/bootstrap/fonts',
                src: '**',
                dest: 'themes/default/fonts/',
            },
            fontawesome: {
                expand: true,
                cwd: './node_modules/font-awesome/fonts',
                src: '**',
                dest: 'themes/default/fonts/',
            },
        },
        less: {
            development: {
                files: filesLess
            },

        },
        wiredep: {
            target: {
                src: 'themes/default/twig/layout.twig',
                ignorePath: '../../..',
//                overrides: wiredepOverrides,
  //              exclude: wiredepExclude
                fileTypes: {
                    twig: {
                        block: /(([ \t]*)<!--\s*bower:*(\S*)\s*-->)(\n|\r|.)*?(<!--\s*endbower\s*-->)/gi,
                        detect: {
                            js: /<script.*src=['"]([^'"]+)/gi,
                            css: /<link.*href=['"]([^'"]+)/gi
                        },
                        replace: {
                            js: '<script src="\{\{ app.request.basepath \}\}{{filePath}}"></script>',
                            css: '<link rel="stylesheet" href="\{\{ app.request.basepath \}\}{{filePath}}" />'
                        }
                    },
                },

            }
        },
        watch: {
            scripts: {
                files: ['themes/default/**/*.*'],
                tasks: ['less'],
                options: {
                    spawn: false,
                },
            },
        }
    })


    grunt.registerTask('default', ['clean','copy', 'build', 'less', 'wiredep']);
    grunt.registerTask('run', ['default', 'watch']);



};