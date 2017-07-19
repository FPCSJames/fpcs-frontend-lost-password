# FPCS Frontend Lost Password
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html) ![Tested to: WP 4.8.x](https://img.shields.io/badge/tested%20up%20to-WP%204.8.x-brightgreen.svg)

An all-in-one frontend password reset solution.

## Usage
Define your login page, password reset page, and "my account"/profile page in the class constants. Add [lostpassword] shortcode to password reset page.

## Styling
This plugin's forms are designed to work with Bootstrap, but will work without it. If your theme does not use Bootstrap, you may wish to implement your own styles, particularly for the label elements, which when using Bootstrap are set to be displayed only to screen readers. [Bootstrap's .sr-only style](https://github.com/twbs/bootstrap/blob/master/less/scaffolding.less#L125) as of 3.3.7 is:

```
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0,0,0,0);
  border: 0;
}
```

## License
This plugin implements code from WordPress core, and is, like WordPress itself, licensed under GPL 2.0 or higher. See `LICENSE.txt`.

> Copyright (C) 2016-2017 Flashpoint Computer Services, LLC <info@flashpointcs.net>
>
> *This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.*
>
> *This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.*
>
> *You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.*