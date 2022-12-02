<form action="loader.php" method="post">
  <fieldset>
    <p class="lead fw-bold">Database Credentials</p>
    <table>
      <tr>
        <td>Server name</td>
        <td><input type="text" name="db[]" required></td>
      </tr>
      <tr>
        <td>Username</td>
        <td><input type="text" name="db[]" required></td>
      </tr>
      <tr>
        <td>Password</td>
        <td><input type="text" name="db[]"></td>
      </tr>
      <tr>
        <td>Database name</td>
        <td><input type="text" name="db[]" required></td>
      </tr>
</table>
  </fieldset>

  <button type="submit">Create</button>
</form>