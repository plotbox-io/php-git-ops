# php-git-ops
PHP wrapper around git designed to help with dev-ops needs. Provides classes that can be 
composed in various ways to perform some advanced logic. 

One core use-case is ability to filter code style issues by file and line number so that 
issues found, but not touched on the current branch, are filtered out. This can help a lot 
when dealing with legacy code-bases where you want to tackle code smells bit by bit. 

**NOTE: Currently in early stage of development (Potentially subject to breaking changes)**

### Example Usage

#### Filter issues by 'lines touched' in current branch 

The logic will consider files added, and modified (unstaged, staged, comitted), 
filtering out any issues deemed to not have been touched in the current branch

```
// Make a line filter
$git = new Git('/path/to/some/repo-directory');
$lineFilterFactory = new LineFilterFactory($git);
$lineFilter = $lineFilterFactory->makeLineFilter();

// Pass in ci-issues to be filtered (third parameter 'attachment' can be anything you like)
$issues = [
    CodeIssue::make('devops/git/post-merge', 123, 'abc123'),
    CodeIssue::make('static/maintenance.html', 456, 'abc456')
];
$filteredIssues = $lineFilter->filterIssues($issues);
```

## Contributing

Please read [CONTRIBUTING.md] for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
