# About Fusion

Fusion is a PHP package manager that removes the distinction between traditional 
root projects and their dependencies by treating PHP projects as generic packages 
built together through a unified modular package system.

## Documentation

The separate [documentation repository](https://gitlab.com/valvoid/fusion/docs)
also has the [user-friendly version](https://valvoid.com/registry/packages/1/fusion-php-package-manager/docs/prologue/fusion) 
and covers the following key features:

- **Projects Are Packages**: Build root projects together with their dependencies.
- **Modular Packages**: Build recursive, open, or circular dependency graphs.
- **Tiered Metadata**: Define production, shared development, and local metadata files.
- **Source Code**: Get an autoloader out of the box.
- **Structure Indicators**: Expose paths that other packages can extend.
- **Lifecycle Hooks**: Attach custom scripts to specific workflow stages.
- **Custom Logic**: Build your own package manager on top of Fusion.
- **Package References**: Resolve packages by semver, commit, branch, or tag.

## Registry

For default packages, see the [default registry](https://valvoid.com/registry).

## Contribution

Each merge request serves as confirmation of ownership transfer to the 
project and must meet the following criteria:

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
