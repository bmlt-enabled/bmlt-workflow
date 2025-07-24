// Copyright (C) 2022 nigel.bmlt@gmail.com
// 
// This file is part of bmlt-workflow.
// 
// bmlt-workflow is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// bmlt-workflow is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with bmlt-workflow.  If not, see <http://www.gnu.org/licenses/>.

const fs = require('fs');
const path = require('path');

// Function to extract the database version from the PHP file
function extractDbVersion() {
  try {
    // Path to the BMLTWF_Database.php file
    const dbFilePath = path.resolve(__dirname, '../../../src/BMLTWF_Database.php');
    
    // Read the file content
    const fileContent = fs.readFileSync(dbFilePath, 'utf8');
    
    // Use regex to find the database version
    const versionMatch = fileContent.match(/public\s+\$bmltwf_db_version\s*=\s*['"]([^'"]+)['"]/);
    
    if (versionMatch && versionMatch[1]) {
      return versionMatch[1];
    }
    
    // Fallback to a default version if not found
    console.warn('Database version not found in BMLTWF_Database.php, using fallback version 1.1.25');
    return '1.1.25';
  } catch (error) {
    console.error('Error reading database version:', error);
    return '1.1.25'; // Fallback version
  }
}

// Export the current database version
export const CURRENT_DB_VERSION = extractDbVersion();