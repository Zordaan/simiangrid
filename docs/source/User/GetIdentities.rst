GetIdentities Method
====================

Retrieve all identities associated with a given user.

Request Format
--------------

+-----------------+----------------------------+--------+-------------+
| *Parameter*     | *Description*              | *Type* | *Required*  |
+=================+============================+========+=============+
| `RequestMethod` | GetIdentities              | String | Yes         |
+-----------------+----------------------------+--------+-------------+
| `UserID`        | UUID of the user to fetch  | UUID   | Optional^1^ |
|                 | identities for             |        |             |
+-----------------+----------------------------+--------+-------------+
| `Identifier`    | Return all identities with | String | Optional^1^ |
|                 | this identifier            |        |             |
+-----------------+----------------------------+--------+-------------+

  * ^1^Either `UserID` or `Identifier` must be specified

Sample request: ::

    RequestMethod=GetIdentities
    &UserID=9ffd5a95-b8bd-4d91-bbed-ded4c80ba151


Response Format
---------------

+--------------+--------------------------------------+------------------+
| *Parameter*  | *Description*                        | *Type*           |
+==============+======================================+==================+
| `Success`    | True if zero or more identities were | Boolean          |
|              | returned, False if a Message was     |                  |
|              | returned                             |                  |
+--------------+--------------------------------------+------------------+
| `Identities` | An array of user identities          | Array, see below |
+--------------+--------------------------------------+------------------+
| `Message`    | Error message                        | String           |
+--------------+--------------------------------------+------------------+


Identity Format
---------------

+--------------+--------------------------------------+---------+
| *Parameter*  | *Description*                        | *Type*  |
+==============+======================================+=========+
| `Identifier` | Identifier, for example a login name | String  |
+--------------+--------------------------------------+---------+
| `Credential` | Credential, for example a hashed     | String  |
|              | password                             |         |
+--------------+--------------------------------------+---------+
| `Type`       | Identity type, such as "md5hash"     | String  | 
+--------------+--------------------------------------+---------+
| `UserID`     | UUID of the user account this        | UUID    |
|              | identity maps to                     |         |
+--------------+--------------------------------------+---------+
| `Enabled`    | True if this identity is enabled,    | Boolean |
|              | otherwise false                      |         |
+--------------+--------------------------------------+---------+


Success: ::

    {
        "Success":true,
        "Identities":
        [
            {
                "Identifier":"jradford",
                "Credential":"$1$5f4dcc3b5aa765d61d8327deb882cf99",
                "Type":"md5hash",
                "UserID":"9ffd5a95-b8bd-4d91-bbed-ded4c80ba151",
                "Enabled":true
            }
        ]
    }


Failure: ::

    {
        "Success":false,
        "Message":"User does not exist"
    }

