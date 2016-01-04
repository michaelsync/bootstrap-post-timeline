/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * some info:
 * http://robandlauren.com/2014/02/05/live-reload-grunt-wordpress/
 */
module.exports = function (grunt) {
// Project configuration.
    grunt.initConfig({
// define source files and their destinations
        less: {
            development: {
                options: {
                    sourceMap: true
                },
                files: {
                    "css/timeline.css": "less/css/timeline.less",
                }
            }
        },
        cssmin: {
            target: {
                files: {
                    'css/timeline.min.css': ['css/timeline.css']
                },
            }
        },
        uglify: {
            options: {
                sourceMap: true
            },
            files: {
                src: ['js/bootstrap-post-timeline.js'], //'js/*.js', // source files mask
//                cwd: 'js/',
                dest: 'js/', // destination folder
                expand: true, // allow dynamic building
                flatten: true, // remove all unnecessary nesting
                ext: '.min.js'   // replace .js to .min.js
            }
        },
        watch: {
            options: {
                livereload: true,
            },
            js: {
                files: ['js/bootstrap-post-timeline.js'],
                tasks: ['uglify']
            },
            styles: {
                files: ['less/**/*.less'], // which files to watch
                tasks: ['less'],
                options: {
//                    spawn: false
                }
            },
            mincss: {
                files: ['css/*.css'],
                tasks: ['cssmin']
            },
            php: {
                files: ['**/*.php'], //files: ['*.php'],
                options: {
                    livereload: true,
                }
            }
        }
    });
// load plugins
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
// register at least this one task
    grunt.registerTask('default', ['less', 'uglify', 'watch']);
};
