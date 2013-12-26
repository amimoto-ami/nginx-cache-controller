/* vim: set ft=javascript expandtab shiftwidth=2 tabstop=2: */

module.exports = function( grunt ) {

  // Project configuration
  grunt.initConfig( {
    pkg:    grunt.file.readJSON( 'package.json' ),
    sass:   {
      all: {
        options: {
          style: 'expanded'
        },
        files: {
          'css/admin.css': 'css/admin.scss'
        }
      }
    },
    cssmin: {
      options: {
        banner: '/**\n' +
            ' * <%= pkg.title %>\n' +
            ' *\n' +
            ' * <%= pkg.author.url %>\n' +
            ' * <%= pkg.repository.url %>\n' +
            ' *\n' +
            ' * Copyright <%= grunt.template.today("yyyy") %>, <%= pkg.author.name %> (<%= pkg.author.url %>)\n' +
            ' * Released under the <%= pkg.license %>\n' +
            ' */\n'
      },
      minify: {
        expand: true,
        cwd: 'css/',
        src: ['admin.css'],
        dest: 'css/',
        ext: '.min.css'
      }
    }
  } );

  // Load other tasks
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-sass');

  // Default task.
  grunt.registerTask('default', ['sass', 'cssmin']);

  grunt.util.linefeed = '\n';
};
