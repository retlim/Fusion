# About Fusion

Fusion is a PHP package manager that manages dependencies, loadable source 
code, extensions, and state of PHP projects.

## Documentation

The separated [documentation repository](https://gitlab.com/valvoid/fusion/docs)
also has the [user-friendly output](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/prologue/fusion) and contains information about the 
following key features:

### Everything Is a Modular Package

The root project, its dependencies, and even Fusion itself are modular packages that 
can be used as standalone software, as dependencies in other projects, or both at the 
same time.

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

### Lifecycle Hooks

Packages can define custom scripts in their metadata that run after recycling, 
downloading, installing, or updating, and before migrating or deleting the package.

### Loadable Code

All object-oriented and procedural code is automatically indexed into granular 
files for custom loading. The same files also form the basis of a pre-built 
autoloader, providing default out-of-the-box loading.

### Flexible Package References

Fusion offers full support for [semantic versioning](https://semver.org), as well
as commit, branch, and tag offsets, which can be extended by intuitive, well-known
logic for [complex references](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure#trailing-reference-segment),
similar to the syntax used in code:

- Logical `&&` and `||` operators.
- Comparison signs `!=`, `==`, `>=`, `<=`, `>` and `<`.
- Grouping brackets `()`.

The resolution process uses a conflict-driven clause learning (CDCL) algorithm
to ensure efficient decision-making.

### Directory Type Indicators

Fusion builds new versions efficiently by recycling existing packages. To 
instruct the package manager that your package includes a mutable directory built by a 
callback and should be handled individually as new content, set the [mutable](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure#mutable-directory-indicator) 
indicator in your metadata.

To allow other packages to extend yours at a special directory using the default 
built-in, out-of-the-box behavior, set the [extendable](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/package/schema/structure#extendable-directory-indicator) 
indicator in your metadata. Fusion will also generate an extensions file for your 
package, containing the parent package dirs and the order in which they extend it, 
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

Fusion - PHP Package Manager  
Copyright © Valvoid

This program is free software: you can redistribute it and/or modify it under the 
terms of the GNU General Public License as published by the Free Software Foundation, 
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this 
program. If not, see [licenses](https://www.gnu.org/licenses/).
