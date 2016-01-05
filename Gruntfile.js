/* 
 * some info:
 * http://robandlauren.com/2014/02/05/live-reload-grunt-wordpress/
 */
module.exports = function (grunt) {
    grunt.initConfig({
        less: {
            development: {
                options: {
                    sourceMap: true
                },
                files: {
                    "css/timeline.css": "less/css/timeline.less"
                }
            }
        },
        cssmin: {
            target: {
                files: {
                    'css/timeline.min.css': ['css/timeline.css']
                }
            }
        },
        uglify: {
            options: {
                sourceMap: true
            },
            files: {
                src: ['js/bootstrap-post-timeline.js'],
                dest: 'js/',
                expand: true,
                flatten: true,
                ext: '.min.js'
            }
        },
        watch: {
            options: {
//                livereload: true
            },
            js: {
                files: ['js/bootstrap-post-timeline.js'],
                tasks: ['uglify']
            },
            styles: {
                files: ['less/**/*.less'],
                tasks: ['less']
            },
            mincss: {
                files: ['css/*.css'],
                tasks: ['cssmin']
            },
            php: {
                files: ['**/*.php']
            }
        },
        mkdir: {
            all: {
                options: {
                    create: ['dist']
                }
            }
        },
        compress: {
            main: {
                options: {
                    archive: 'bootstrap-post-timeline.zip'
                },
                expand: true,
                src: [
                    'css/*',
                    'images/*',
                    'js/*',
                    'themes/**',
                    'includes/*',
                    'index.php',
                    'bootstrap-post-timeline.php',
                    'LICENSE',
                    'readme.txt',
                    'screenshot-1.jpg',
                    'screenshot-2.jpg',
                ],
                dest: 'bootstrap-post-timeline/'
            }
        },
        rename: {
            main: {
                files: [
                    {src: ['bootstrap-post-timeline.zip'], dest: 'dist/bootstrap-post-timeline.zip'}
                ]
            }
        }
    });
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.registerTask('default', ['less', 'uglify', 'watch']);

    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-mkdir');
    grunt.loadNpmTasks('grunt-contrib-rename');
    grunt.registerTask('package', ['mkdir', 'compress', 'rename']);
};
