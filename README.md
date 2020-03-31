# php-git-ops
PHP wrapper around git designed to help with devops needs

Currently in early stage of development (Potentially subject to breaking changes)

### Example

```
// Make a line filter
$git = new Git('/home/richard/Development/PlotBox/plotbox-app');
$lineFilterFactory = new LineFilterFactory($git);
$lineFilter = $lineFilterFactory->makeLineFilter();

// Pass in ci-issues to be filtered
$issues = [
    CodeIssue::make('devops/git/post-merge', 123, 'abc123'),
    CodeIssue::make('static/maintenance.html', 456, 'abc456')
];
$filteredIssues = $lineFilter->filterIssues($issues);
```

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/PurpleBooth/b24679402957c63ec426) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
