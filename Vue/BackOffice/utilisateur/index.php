<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Cre8Connect</title>
    <link rel="stylesheet" href="../css/backoffice.css">
</head>
<body>
    <header>
        <h1>User Management Dashboard</h1>
        <p>Manage user accounts, profiles, and authentication</p>
    </header>

    <main>
        <section class="login-section">
            <h2>Login</h2>
            <div class="login-form">
                <form action="#" method="post">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn-login">Login</button>
                </form>
            </div>
        </section>

        <section class="register-section">
            <h2>Register New User</h2>
            <div class="register-form">
                <form action="#" method="post">
                    <div class="form-group">
                        <label for="reg-name">Name:</label>
                        <input type="text" id="reg-name" name="name" placeholder="Enter your name">
                    </div>
                    <div class="form-group">
                        <label for="reg-email">Email:</label>
                        <input type="email" id="reg-email" name="email" placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="reg-password">Password:</label>
                        <input type="password" id="reg-password" name="password" placeholder="Create a password">
                    </div>
                    <button type="submit" class="btn-register">Register</button>
                </form>
            </div>
        </section>

        <section class="user-list-section">
            <h2>User List</h2>
            <div class="user-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>John Doe</td>
                            <td>john@example.com</td>
                            <td>User</td>
                            <td>Active</td>
                            <td>
                                <button class="btn-edit">Edit</button>
                                <button class="btn-delete">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Jane Smith</td>
                            <td>jane@example.com</td>
                            <td>Admin</td>
                            <td>Active</td>
                            <td>
                                <button class="btn-edit">Edit</button>
                                <button class="btn-delete">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2023 Cre8Connect. All rights reserved.</p>
    </footer>
</body>
</html>