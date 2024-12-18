# Roadmap

  - [ ] Allow custom default HTML template
  - [ ] `parserToHtml` should also be one ParserInterface
  - [ ] Add `x-document-path` to headers to identify the served md file
  - [ ] Make `parserToJson` optional
  - [ ] throw error on unsupported format instead of serving md (ex. index.wav will serve md file)
  - [ ] Write tests for consistent routing
  - [x] Enable support for more markdown file extensions :
      .mdwn
      .mdown
      .mdtxt
      .mdtext
      .markdown
      ~~.text~~

### Ideas
*Should we support parserTo<format> configuration to enable more formats?*
Maybe if we tie the default format to a parser, also if there's a parser available 
for a format that should be used.


*Should we add support for some kind of indexing and search?* 
This would make it more 
like a documentation generator, and there are plenty. 
Also unscrew does not aim to be used as a static site generator by itself. 
It may be used as a backend or alongside a custom, featureful parser.
