# About Fusion

Fusion is a PHP package manager that enhances productivity in PHP-based
projects. It simplifies development and maintenance by automating
repetitive tasks such as managing dependencies, migrating package states, extending 
packages, and handling loadable code.

## Documentation

The separated [documentation repository](https://gitlab.com/valvoid/fusion/docs)
also has the [user-friendly output](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/prologue/fusion) and contains information about the 
following key features:

### Everything Is a Modular Package

To keep things simple and easy to use, Fusion handles everything, including your 
project, its dependencies, and even the package manager itself, as a modular 
package that can be standalone, a nested dependency of another, or both at the 
same time. This is possible since each package has its own [custom directory structure](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure),
which you can define in the metadata file as you like.

### Scoped Metadata and Snapshot Files

A package can be defined using three individual [metadata files](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/files), 
each serving a different use case:

- Optional local development metadata specific to your personal machine.
- Optional shared development metadata used across all machines in the project.
- Production metadata used for releases.

These files intersect in a top-down order, where local metadata overrides shared 
metadata, and shared metadata overrides production metadata. Fusion also generates 
a snapshot file for each metadata file, capturing replicable versions of its 
dependencies.

### Lifecycle Callbacks

Each time Fusion builds a new version, it looks for adaptive packages that need 
to be notified about the change. To make your package this type, add [lifecycle 
callbacks](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/lifecycle) 
in your metadata at the key stages:

- After the package is recycled, downloaded, installed, or updated.
- Before the package is migrated or deleted.

### Auto-Generated Code Registries

When [package identifier](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/primitive#id)
segments, defined in your metadata and separated by `/`, match or prefix [code namespace](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/code)
segments, separated by `\`, Fusion automatically registers your code in two files
for lazy and ASAP (as soon as possible) loading.

- The lazy file contains OOP (Object-Oriented Programming) code for autoloading on demand.
- The ASAP file contains preloadable procedural code.

These files are stored in a [custom cache directory](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure#cache)
relative to the package root and can be used individually within your package or
combined into a common autoloader for the root package.


### Flexible Package References

Fusion offers full support for [semantic versioning](https://semver.org), as well
as commit, branch, and tag offsets, which can be extended by intuitive, well-known
logic for [complex references](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure#reference),
similar to the syntax used in code:

- Logical `&&` and `||` operators.
- Comparison signs `!=`, `==`, `>=`, `<=`, `>` and `<`.
- Grouping brackets `()`.

The resolution process uses a conflict-driven clause learning (CDCL) algorithm
to ensure efficient decision-making.

### Directory Type Indicators

Fusion builds new versions efficiently by recycling existing packages. To 
instruct the package manager that your package includes a mutable directory built by a 
callback and should be handled individually as new content, set the [state](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure#states) 
indicator in your metadata.

To allow other packages to extend yours at a special directory using the default 
built-in, out-of-the-box behavior, set the [extension](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure#extensions) 
indicator in your metadata. Fusion will also generate an extensions file for your 
package, containing the parent package IDs and the order in which they extend it, 
in case your package needs to know this.

### Interface-Based Customization

As mentioned above, Fusion is itself a [package](https://valvoid.com/registry/packages/1/fusion-php-package-manager), 
and its architecture supports out-of-the-box customization through built-in directory 
indicators. You can build your [own package manager](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/extensions/package) 
on top by extending it with custom implementations, such as:

- Adding a custom package registry.
- Replacing the download, build, or replication logic.
- Setting a custom log serializer.

## Registry

For default packages, see the [default registry](https://valvoid.com/registry) page.

## Contribution

Each merge request serves as confirmation to transfer ownership to the project 
and must meet the following criteria:

- Own intellectual property.
- Neutral content. Free from political bias, for example.
- Pure PHP without exotic extensions.

See the contributing file if these criteria apply to you.

## License

Fusion. A package manager for PHP-based projects.  
Copyright Valvoid

This program is free software: you can redistribute it and/or modify it under the 
terms of the GNU General Public License as published by the Free Software Foundation, 
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this 
program. If not, see [licenses](https://www.gnu.org/licenses/).
