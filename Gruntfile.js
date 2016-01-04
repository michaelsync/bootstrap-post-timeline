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
        }
    });
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.registerTask('default', ['less', 'uglify', 'watch']);
};
